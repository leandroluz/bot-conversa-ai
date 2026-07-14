<?php

use Adianti\Service\AdiantiRestService;

/**
 * Bot FAQ retrieval service (RAG support)
 */
class AppBotFaqSearchService implements AdiantiRestService
{
    /**
     * Search FAQ answers for a given bot using vector similarity when available,
     * with textual fallback.
     */
    public static function search($request)
    {
        if (empty($request['query'])) {
            throw new Exception('Parâmetro obrigatório: query');
        }

        TTransaction::open('permission');

        try {
            $bot = self::resolveBot($request);
            if (!$bot) {
                throw new Exception('Bot não encontrado');
            }

            if (($bot->ativo ?? 'N') !== 'Y') {
                throw new Exception('Bot inativo');
            }

            $topK = (int) ($request['top_k'] ?? $bot->faq_top_k ?? 5);
            $topK = max(1, min(50, $topK));

            $minSimilarity = (float) ($request['min_similarity'] ?? $bot->similaridade_minima ?? 0.75);
            $minSimilarity = max(0, min(1, $minSimilarity));

            $query = trim((string) $request['query']);
            $embedding = self::parseEmbedding($request['embedding'] ?? null);

            $results = [];
            $strategy = 'text';

            if (($bot->usar_rag ?? 'N') === 'Y' && !empty($embedding)) {
                $results = self::searchByVector($bot->id, $embedding, $topK, $minSimilarity);
                if (!empty($results)) {
                    $strategy = 'vector';
                }
            }

            if (empty($results)) {
                $results = self::searchByText($bot->id, $query, $topK);
                $strategy = 'text';
            }

            TTransaction::close();

            return [
                'bot' => [
                    'id' => $bot->id,
                    'nome' => $bot->nome,
                    'system_unit_id' => $bot->system_unit_id,
                    'evolution_instance' => $bot->evolution_instance,
                    'instrucoes' => $bot->instrucoes,
                    'modelo_llm' => $bot->modelo_llm,
                    'temperatura' => (float) $bot->temperatura,
                    'top_p' => isset($bot->top_p) ? (float) $bot->top_p : 1.0,
                    'max_tokens' => isset($bot->max_tokens) ? (int) $bot->max_tokens : 1000,
                    'max_messages_context' => isset($bot->max_messages_context) ? (int) $bot->max_messages_context : 50,
                    'split_long_replies' => $bot->split_long_replies ?? 'Y',
                    'avoid_repetition' => $bot->avoid_repetition ?? 'N',
                    'wait_seconds' => isset($bot->wait_seconds) ? (int) $bot->wait_seconds : 1,
                    'human_handoff_pause_minutes' => isset($bot->human_handoff_pause_minutes) ? (int) $bot->human_handoff_pause_minutes : 5,
                    'allow_audio' => $bot->allow_audio ?? 'Y',
                    'allow_image' => $bot->allow_image ?? 'Y',
                    'allow_pdf' => $bot->allow_pdf ?? 'Y',
                    'allow_code_interpreter' => $bot->allow_code_interpreter ?? 'N',
                    'allow_web_search' => $bot->allow_web_search ?? 'N',
                    'usar_rag' => $bot->usar_rag,
                    'faq_top_k' => (int) $bot->faq_top_k,
                    'similaridade_minima' => (float) $bot->similaridade_minima,
                ],
                'actions' => self::getBotActions($bot->id),
                'query' => $query,
                'strategy' => $strategy,
                'count' => count($results),
                'results' => $results,
            ];
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            throw $e;
        }
    }

    /**
     * Persist embedding into FAQ record.
     */
    public static function saveEmbedding($request)
    {
        if (empty($request['faq_id'])) {
            throw new Exception('Parâmetro obrigatório: faq_id');
        }

        $embedding = self::parseEmbedding($request['embedding'] ?? null);
        if (empty($embedding)) {
            throw new Exception('Parâmetro obrigatório: embedding (array de números)');
        }

        TTransaction::open('permission');

        try {
            $faq = new AppBotFaq($request['faq_id']);
            if (empty($faq->id)) {
                throw new Exception('FAQ não encontrada');
            }

            $pdo = TTransaction::get();
            $arrayLiteral = self::toPgArrayLiteral($embedding);
            $vectorLiteral = self::toPgVectorLiteral($embedding);
            $vectorSchema = self::getVectorSchema($pdo);

            $sql = "UPDATE app.app_bot_faq
                       SET embedding_array = :embedding_array,
                           atualizado_em = NOW()
                     WHERE id = :faq_id";

            if (!empty($vectorSchema) && self::hasColumn($pdo, 'app', 'app_bot_faq', 'embedding_vector')) {
                $sql = "UPDATE app.app_bot_faq
                           SET embedding_array = :embedding_array,
                               embedding_vector = CAST(:embedding_vector AS {$vectorSchema}.vector),
                               atualizado_em = NOW()
                         WHERE id = :faq_id";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':embedding_array', $arrayLiteral);
            $stmt->bindValue(':faq_id', $request['faq_id']);

            if (strpos($sql, ':embedding_vector') !== false) {
                $stmt->bindValue(':embedding_vector', $vectorLiteral);
            }

            $stmt->execute();

            TTransaction::close();

            return [
                'faq_id' => $request['faq_id'],
                'size' => count($embedding),
                'vector_saved' => strpos($sql, ':embedding_vector') !== false,
            ];
        } catch (Exception $e) {
            if (TTransaction::get()) {
                TTransaction::rollback();
            }
            throw $e;
        }
    }

    private static function resolveBot($request)
    {
        $where = [];
        $params = [];

        if (!empty($request['bot_id'])) {
            $where[] = 'b.id = :bot_id';
            $params[':bot_id'] = $request['bot_id'];
        }

        if (!empty($request['evolution_instance'])) {
            $where[] = 'b.evolution_instance = :evolution_instance';
            $params[':evolution_instance'] = $request['evolution_instance'];
        }

        if (!empty($request['system_unit_id'])) {
            $where[] = 'b.system_unit_id = :system_unit_id';
            $params[':system_unit_id'] = $request['system_unit_id'];
        }

        if (empty($where)) {
            throw new Exception('Informe bot_id ou evolution_instance (opcionalmente system_unit_id)');
        }

        $sql = 'SELECT b.*
                  FROM app.app_bot b
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY b.ativo DESC, b.nome ASC
                 LIMIT 1';

        $stmt = TTransaction::get()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $bot = new stdClass;
        foreach ($row as $k => $v) {
            $bot->$k = $v;
        }

        return $bot;
    }

    private static function searchByVector($botId, array $embedding, $topK, $minSimilarity)
    {
        $pdo = TTransaction::get();
        $vectorSchema = self::getVectorSchema($pdo);

        if (empty($vectorSchema) || !self::hasColumn($pdo, 'app', 'app_bot_faq', 'embedding_vector')) {
            return [];
        }

        $sql = "SELECT f.id,
                       f.pergunta,
                       f.resposta,
                       f.palavras_chave,
                       f.fonte_externa,
                       f.ordem,
                       (1 - (f.embedding_vector <=> CAST(:embedding AS {$vectorSchema}.vector))) AS score
                  FROM app.app_bot_faq f
                 WHERE f.app_bot_id = :bot_id
                   AND f.ativo = 'Y'
                   AND f.embedding_vector IS NOT NULL
              ORDER BY f.embedding_vector <=> CAST(:embedding AS {$vectorSchema}.vector), f.ordem ASC
                 LIMIT {$topK}";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':bot_id', $botId);
        $stmt->bindValue(':embedding', self::toPgVectorLiteral($embedding));
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $score = isset($row['score']) ? (float) $row['score'] : 0.0;
            if ($score < $minSimilarity) {
                continue;
            }

            $row['score'] = round($score, 6);
            $items[] = $row;
        }

        return $items;
    }

    private static function searchByText($botId, $query, $topK)
    {
        $sql = "SELECT f.id,
                       f.pergunta,
                       f.resposta,
                       f.palavras_chave,
                       f.fonte_externa,
                       f.ordem,
                       ts_rank(
                           to_tsvector('simple', coalesce(f.pergunta,'') || ' ' || coalesce(f.resposta,'') || ' ' || coalesce(f.palavras_chave,'')),
                           plainto_tsquery('simple', :q)
                       ) AS score
                  FROM app.app_bot_faq f
                 WHERE f.app_bot_id = :bot_id
                   AND f.ativo = 'Y'
                   AND (
                       to_tsvector('simple', coalesce(f.pergunta,'') || ' ' || coalesce(f.resposta,'') || ' ' || coalesce(f.palavras_chave,'')) @@ plainto_tsquery('simple', :q)
                       OR f.pergunta ILIKE :like_q
                       OR f.resposta ILIKE :like_q
                       OR f.palavras_chave ILIKE :like_q
                   )
              ORDER BY score DESC, f.ordem ASC
                 LIMIT {$topK}";

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->bindValue(':bot_id', $botId);
        $stmt->bindValue(':q', $query);
        $stmt->bindValue(':like_q', '%'.$query.'%');
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['score'] = isset($row['score']) ? round((float) $row['score'], 6) : 0.0;
            $items[] = $row;
        }

        return $items;
    }

    private static function parseEmbedding($embedding)
    {
        if ($embedding === null || $embedding === '') {
            return [];
        }

        if (is_string($embedding)) {
            $decoded = json_decode($embedding, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $embedding = $decoded;
            } else {
                $embedding = explode(',', $embedding);
            }
        }

        if (!is_array($embedding)) {
            throw new Exception('embedding deve ser array numérico ou string JSON');
        }

        $vector = [];
        foreach ($embedding as $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (!is_numeric($value)) {
                throw new Exception('embedding contém valor não numérico');
            }
            $vector[] = (float) $value;
        }

        return $vector;
    }

    /**
     * Return active bot actions sorted by order
     */
    private static function getBotActions($botId)
    {
        $sql = "SELECT id, nome, tipo, gatilho, resposta_fixa, config_json, ordem, ativo
                  FROM app.app_bot_action
                 WHERE app_bot_id = :bot_id
                   AND ativo = 'Y'
              ORDER BY ordem ASC, criado_em ASC";

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->bindValue(':bot_id', $botId);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $config = [];
            if (!empty($row['config_json'])) {
                $decoded = json_decode((string) $row['config_json'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $config = $decoded;
                }
            }

            $items[] = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'tipo' => $row['tipo'],
                'gatilho' => $row['gatilho'],
                'resposta_fixa' => $row['resposta_fixa'],
                'config' => $config,
                'ordem' => (int) ($row['ordem'] ?? 0),
            ];
        }

        return $items;
    }

    private static function getVectorSchema(PDO $pdo)
    {
        $stmt = $pdo->query("SELECT n.nspname
                               FROM pg_type t
                               JOIN pg_namespace n ON n.oid = t.typnamespace
                              WHERE t.typname = 'vector'
                              ORDER BY CASE WHEN n.nspname = 'public' THEN 0 ELSE 1 END
                              LIMIT 1");
        $schema = $stmt->fetchColumn();

        if (!$schema) {
            return null;
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $schema)) {
            throw new Exception('Schema inválido para tipo vector');
        }

        return $schema;
    }

    private static function hasColumn(PDO $pdo, $schema, $table, $column)
    {
        $sql = "SELECT 1
                  FROM information_schema.columns
                 WHERE table_schema = :schema
                   AND table_name = :table
                   AND column_name = :column";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table' => $table,
            ':column' => $column,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private static function toPgArrayLiteral(array $vector)
    {
        return '{' . implode(',', array_map(function ($value) {
            return (string) $value;
        }, $vector)) . '}';
    }

    private static function toPgVectorLiteral(array $vector)
    {
        return '[' . implode(',', array_map(function ($value) {
            return (string) $value;
        }, $vector)) . ']';
    }
}

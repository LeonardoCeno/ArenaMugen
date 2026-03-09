<?php

declare(strict_types=1);

require_once __DIR__ . '/Personagem.php';
require_once __DIR__ . '/Guerreiro.php';
require_once __DIR__ . '/gojopasta/Gojo.php';
require_once __DIR__ . '/sanspasta/Sans.php';
require_once __DIR__ . '/ExcecaoJogo.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function receberJson(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function classePorChave(string $chave): ?string {
    return match ($chave) {
        'guerreiro' => Guerreiro::class,
        'gojo' => Gojo::class,
        'sans' => Sans::class,
        default => null,
    };
}

function exportarPersonagem(Personagem $personagem, string $label): array {
    $reflection = new ReflectionClass($personagem);

    return [
        'label' => $label,
        'nome' => $personagem->getNome(),
        'classe' => strtolower($reflection->getShortName()),
        'classeNome' => $reflection->getShortName(),
        'vidaAtual' => $personagem->getVidaAtual(),
        'vidaMaxima' => $personagem->getVidaMaxima(),
        'energiaAtual' => $personagem->getEnergiaAtual(),
        'energiaMaxima' => $personagem->getEnergiaMaxima(),
    ];
}

function montarAcoesDisponiveis(Personagem $atual): array {
    $acoes = [
        [
            'type' => 'attack',
            'label' => 'ATACAR',
            'skillName' => 'Ataque',
        ],
        [
            'type' => 'defend',
            'label' => 'DEFENDER',
            'skillName' => 'Defesa',
        ],
    ];

    foreach ($atual->getHabilidades() as $index => $habilidade) {
        $acoes[] = [
            'type' => 'skill',
            'label' => strtoupper((string)$habilidade['nome']),
            'skillName' => (string)$habilidade['nome'],
            'skillIndex' => $index,
        ];
    }

    return $acoes;
}

function exportarEstado(?string $mensagem = null): array {
    if (!isset($_SESSION['game']) || !is_array($_SESSION['game'])) {
        return [
            'started' => false,
            'message' => $mensagem,
        ];
    }

    $game = $_SESSION['game'];

    /** @var Personagem $p1 */
    $p1 = $game['p1'];
    /** @var Personagem $p2 */
    $p2 = $game['p2'];

    $currentKey = $game['currentKey'];
    $atual = $currentKey === 'p1' ? $p1 : $p2;

    $winner = null;
    if (!$p1->estaVivo()) {
        $winner = 'p2';
    } elseif (!$p2->estaVivo()) {
        $winner = 'p1';
    }

    return [
        'started' => true,
        'turno' => $game['turno'],
        'currentKey' => $currentKey,
        'winner' => $winner,
        'p1' => exportarPersonagem($p1, 'Jogador 1'),
        'p2' => exportarPersonagem($p2, 'Jogador 2'),
        'availableActions' => $winner ? [] : montarAcoesDisponiveis($atual),
        'message' => $mensagem,
    ];
}

function garantirPartidaAtiva(): array {
    if (!isset($_SESSION['game']) || !is_array($_SESSION['game'])) {
        throw new EntradaInvalidaException();
    }

    return $_SESSION['game'];
}

$input = receberJson();
$action = (string)($input['action'] ?? 'state');

try {
    if ($action === 'start') {
        $p1Name = trim((string)($input['p1Name'] ?? 'Jogador 1'));
        $p2Name = trim((string)($input['p2Name'] ?? 'Jogador 2'));

        $p1ClassKey = strtolower(trim((string)($input['p1Class'] ?? 'guerreiro')));
        $p2ClassKey = strtolower(trim((string)($input['p2Class'] ?? 'gojo')));

        $p1Class = classePorChave($p1ClassKey);
        $p2Class = classePorChave($p2ClassKey);

        if ($p1Class === null || $p2Class === null) {
            throw new EntradaInvalidaException();
        }

        /** @var Personagem $p1 */
        $p1 = new $p1Class($p1Name !== '' ? $p1Name : 'Jogador 1');
        /** @var Personagem $p2 */
        $p2 = new $p2Class($p2Name !== '' ? $p2Name : 'Jogador 2');

        $_SESSION['game'] = [
            'p1' => $p1,
            'p2' => $p2,
            'turno' => 1,
            'currentKey' => 'p1',
        ];

        responder([
            'ok' => true,
            'state' => exportarEstado('Partida iniciada.'),
        ]);
    }

    if ($action === 'state') {
        responder([
            'ok' => true,
            'state' => exportarEstado(),
        ]);
    }

    if ($action === 'action') {
        $game = garantirPartidaAtiva();

        /** @var Personagem $p1 */
        $p1 = $game['p1'];
        /** @var Personagem $p2 */
        $p2 = $game['p2'];

        $currentKey = $game['currentKey'];
        $atual = $currentKey === 'p1' ? $p1 : $p2;
        $oponente = $currentKey === 'p1' ? $p2 : $p1;

        $actionType = (string)($input['actionType'] ?? '');
        $mensagem = '';

        if ($actionType === 'attack') {
            $mensagem = $atual->atacar($oponente);
        } elseif ($actionType === 'defend') {
            $mensagem = $atual->defender();
        } elseif ($actionType === 'skill') {
            $skillIndex = (int)($input['skillIndex'] ?? -1);
            $habilidades = $atual->getHabilidades();

            if (!isset($habilidades[$skillIndex])) {
                throw new EntradaInvalidaException();
            }

            $habilidade = $habilidades[$skillIndex];
            $metodo = (string)$habilidade['metodo'];
            $precisaAlvo = (bool)$habilidade['precisaAlvo'];

            if ($precisaAlvo) {
                $mensagem = $atual->$metodo($oponente);
            } else {
                $mensagem = $atual->$metodo();
            }
        } else {
            throw new EntradaInvalidaException();
        }

        $atual->iniciarTurno();

        if ($p1->estaVivo() && $p2->estaVivo()) {
            $game['turno']++;
            $game['currentKey'] = $currentKey === 'p1' ? 'p2' : 'p1';
        }

        $game['p1'] = $p1;
        $game['p2'] = $p2;

        $_SESSION['game'] = $game;

        responder([
            'ok' => true,
            'message' => $mensagem,
            'state' => exportarEstado($mensagem),
        ]);
    }

    throw new EntradaInvalidaException();
} catch (ExcecaoJogo $e) {
    responder([
        'ok' => false,
        'message' => $e->getMessage(),
        'state' => exportarEstado($e->getMessage()),
    ], 400);
} catch (Throwable $e) {
    responder([
        'ok' => false,
        'message' => 'Erro interno no servidor.',
    ], 500);
}

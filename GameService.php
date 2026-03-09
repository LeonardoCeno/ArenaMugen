<?php

declare(strict_types=1);

require_once __DIR__ . '/Personagem.php';
require_once __DIR__ . '/Guerreiro.php';
require_once __DIR__ . '/gojopasta/Gojo.php';
require_once __DIR__ . '/sanspasta/Sans.php';
require_once __DIR__ . '/ExcecaoJogo.php';

class GameService {
    public static function getClassMap(): array {
        return [
            'guerreiro' => Guerreiro::class,
            'gojo' => Gojo::class,
            'sans' => Sans::class,
        ];
    }

    public static function getCharacterCatalog(): array {
        $catalogo = [];

        foreach (self::getClassMap() as $key => $className) {
            $catalogo[] = [
                'key' => $key,
                'class' => $className,
                'description' => $className::getDescricao(),
            ];
        }

        return $catalogo;
    }

    public static function createCharacter(string $classKey, string $name): Personagem {
        $normalizedKey = strtolower(trim($classKey));
        $className = self::getClassMap()[$normalizedKey] ?? null;

        if ($className === null) {
            throw new EntradaInvalidaException();
        }

        $normalizedName = trim($name);
        if ($normalizedName === '') {
            $normalizedName = 'Jogador';
        }

        return new $className($normalizedName);
    }

    public static function createGameState(Personagem $p1, Personagem $p2): array {
        return [
            'p1' => $p1,
            'p2' => $p2,
            'turno' => 1,
            'currentKey' => 'p1',
        ];
    }

    public static function determineWinner(array $game): ?string {
        /** @var Personagem $p1 */
        $p1 = $game['p1'];
        /** @var Personagem $p2 */
        $p2 = $game['p2'];

        if (!$p1->estaVivo()) {
            return 'p2';
        }

        if (!$p2->estaVivo()) {
            return 'p1';
        }

        return null;
    }

    public static function getCurrentAndOpponent(array $game): array {
        /** @var Personagem $p1 */
        $p1 = $game['p1'];
        /** @var Personagem $p2 */
        $p2 = $game['p2'];

        $currentKey = ($game['currentKey'] ?? 'p1') === 'p2' ? 'p2' : 'p1';
        $current = $currentKey === 'p1' ? $p1 : $p2;
        $opponent = $currentKey === 'p1' ? $p2 : $p1;

        return [$currentKey, $current, $opponent];
    }

    public static function buildAvailableActions(Personagem $current): array {
        $actions = [
            [
                'type' => 'attack',
                'label' => 'ATACAR',
                'skillName' => 'Ataque',
                'targetsOpponent' => true,
            ],
            [
                'type' => 'defend',
                'label' => 'DEFENDER',
                'skillName' => 'Defesa',
                'targetsOpponent' => false,
            ],
        ];

        foreach ($current->getHabilidades() as $index => $habilidade) {
            $targetsOpponent = (bool)$habilidade['precisaAlvo'];
            $actions[] = [
                'type' => 'skill',
                'label' => strtoupper((string)$habilidade['nome']),
                'skillName' => (string)$habilidade['nome'],
                'skillIndex' => $index,
                'targetsOpponent' => $targetsOpponent,
            ];
        }

        return $actions;
    }

    public static function executeAction(Personagem $current, Personagem $opponent, string $actionType, ?int $skillIndex = null): string {
        if ($actionType === 'attack') {
            return $current->atacar($opponent);
        }

        if ($actionType === 'defend') {
            return $current->defender();
        }

        if ($actionType === 'skill') {
            $habilidades = $current->getHabilidades();
            if ($skillIndex === null || !isset($habilidades[$skillIndex])) {
                throw new EntradaInvalidaException();
            }

            $habilidade = $habilidades[$skillIndex];
            $metodo = (string)$habilidade['metodo'];
            $precisaAlvo = (bool)$habilidade['precisaAlvo'];

            if ($precisaAlvo) {
                return $current->$metodo($opponent);
            }

            return $current->$metodo();
        }

        throw new EntradaInvalidaException();
    }

    public static function performTurn(array &$game, string $actionType, ?int $skillIndex = null): string {
        [$currentKey, $current, $opponent] = self::getCurrentAndOpponent($game);

        $message = self::executeAction($current, $opponent, $actionType, $skillIndex);

        if (self::determineWinner($game) === null) {
            $game['turno'] = ((int)$game['turno']) + 1;
            $game['currentKey'] = $currentKey === 'p1' ? 'p2' : 'p1';

            [, $nextCurrent] = self::getCurrentAndOpponent($game);
            $nextCurrent->iniciarTurno();
        }

        return $message;
    }

    public static function exportCharacter(Personagem $character, string $label): array {
        $reflection = new ReflectionClass($character);

        return [
            'label' => $label,
            'nome' => $character->getNome(),
            'classe' => strtolower($reflection->getShortName()),
            'classeNome' => $reflection->getShortName(),
            'vidaAtual' => $character->getVidaAtual(),
            'vidaMaxima' => $character->getVidaMaxima(),
            'energiaAtual' => $character->getEnergiaAtual(),
            'energiaMaxima' => $character->getEnergiaMaxima(),
            'defendendo' => $character->estaDefendendo(),
            'visual' => $character->getConfiguracaoVisual(),
        ];
    }

    public static function exportState(array $game, ?string $message = null): array {
        /** @var Personagem $p1 */
        $p1 = $game['p1'];
        /** @var Personagem $p2 */
        $p2 = $game['p2'];

        [$currentKey, $current] = self::getCurrentAndOpponent($game);
        $winner = self::determineWinner($game);

        return [
            'started' => true,
            'turno' => (int)$game['turno'],
            'currentKey' => $currentKey,
            'winner' => $winner,
            'p1' => self::exportCharacter($p1, 'Jogador 1'),
            'p2' => self::exportCharacter($p2, 'Jogador 2'),
            'availableActions' => $winner ? [] : self::buildAvailableActions($current),
            'message' => $message,
        ];
    }
}

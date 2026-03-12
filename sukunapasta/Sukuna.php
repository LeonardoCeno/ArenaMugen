<?php

require_once __DIR__ . '/../Personagem.php';

class Sukuna extends Personagem {

    const CUSTO_DESMANTELAR = 250;
    const CUSTO_KAMINO_FUGA = 800;
    const CUSTO_DOMAIN = 1200;
    const CUSTO_REVERSE = 500;
    const REGENERACAO_PROPRIA = 70;

    public function __construct(string $nome) {
        parent::__construct($nome, 200, 25, 4000);
    }

    public static function getDescricao(): string {
        return "Sukuna (Alto HP, ataque médio, habilidades: Desmantelar, Kamino Fuga, Reverse Energy e Domain)";
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        $this->consumirEnergia(self::CUSTO_DESMANTELAR);
        $danoReal = (int) ceil(max(0, $this->ataque) * 1.5);
        $resultado = $this->executarAtaqueDireto($alvo, "Desmantelar", $danoReal);

        if (!$resultado['acertou']) {
            return $resultado['mensagem'];
        }

        $danoBleed = (int) ceil($danoReal * 0.40);
        if ($danoBleed > 0) {
            $alvo->aplicarSangramento($danoBleed, 2);
        }

        $mensagem = $resultado['mensagem'];

        if ($danoBleed > 0) {
            $mensagem .= " Sangramento aplicado por 2 turnos ({$danoBleed} por turno).";
        }

        return $mensagem;
    }

    public function kaminoFuga(Personagem $alvo): string {
        $this->consumirEnergia(self::CUSTO_KAMINO_FUGA);
        $danoReal = (int) ceil(max(0, $this->ataque) * 2);
        $resultado = $this->executarAtaqueDireto($alvo, "Kamino Fuga", $danoReal);

        if (!$resultado['acertou']) {
            return $resultado['mensagem'];
        }

        $danoBurn = (int) ceil($danoReal * 0.40);
        if ($danoBurn > 0) {
            $alvo->aplicarQueimadura($danoBurn, 1);
        }

        $mensagem = $resultado['mensagem'];

        if ($danoBurn > 0) {
            $mensagem .= " Burn aplicado por 1 turno ({$danoBurn} por turno).";
        }

        return $mensagem;
    }

    public function reverseEnergy(): string {
        $this->consumirEnergia(self::CUSTO_REVERSE);
        $this->curarVida(50);

        return $this->formatarMensagemAcaoSemAlvo("Reverse Energy");
    }

    public function santuarioMalevolente(Personagem $alvo): string {
        $this->consumirEnergia(self::CUSTO_DOMAIN);
        $danoReal = 70;
        $resultado = $this->executarAtaqueDireto($alvo, "Domain", $danoReal);

        if (!$resultado['acertou']) {
            return $resultado['mensagem'];
        }

        $danoBleed = (int) ceil($danoReal * 0.50);
        if ($danoBleed > 0) {
            $alvo->aplicarSangramento($danoBleed, 4);
        }

        $mensagem = $resultado['mensagem'];

        if ($danoBleed > 0) {
            $mensagem .= " Sangramento aplicado por 4 turnos ({$danoBleed} por turno).";
        }

        return $mensagem;
    }

    public function getHabilidades(): array {
        return [
            [
                "nome" => "Desmantelar",
                "metodo" => "usarHabilidadeEspecial",
                "precisaAlvo" => true
            ],
            [
                "nome" => "Kamino Fuga",
                "metodo" => "kaminoFuga",
                "precisaAlvo" => true
            ],
            [
                "nome" => "Reverse Energy",
                "metodo" => "reverseEnergy",
                "precisaAlvo" => false
            ],
            [
                "nome" => "Domain",
                "metodo" => "santuarioMalevolente",
                "precisaAlvo" => true
            ]
        ];
    }

    public function getDescricoesAcoes(): array {
        return array_merge(parent::getDescricoesAcoes(), [
            'Desmantelar' => 'Causa 38 de dano. Bleed: 16 por turno por 2 turnos. Custo: ' . self::CUSTO_DESMANTELAR . ' energia.',
            'Kamino Fuga' => 'Causa 50 de dano. Burn: 20 por turno por 1 turno. Custo: ' . self::CUSTO_KAMINO_FUGA . ' energia.',
            'Reverse Energy' => 'Cura 50 de vida. Custo: ' . self::CUSTO_REVERSE . ' energia.',
            'Domain' => 'Causa 70 de dano. Bleed: por 4 turnos. Custo: ' . self::CUSTO_DOMAIN . ' energia.',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './sukunapasta/sprites/sukunabasefinal.png',
            'winImage' => './sukunapasta/sprites/sukunawin.jpg',
            'actions' => [
                'Ataque' => [
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/sukuachute1.png',
                            'durationMs' => 400,
                        ],
                        [
                            'sprite' => './sukunapasta/sprites/sukunachute2real.png',
                            'durationMs' => 400,
                        ],
                    ],
                ],
                'Desmantelar' => [
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/sukuacleave.png',
                            'durationMs' => 1000,
                        ],
                    ],
                    'overlays' => [
                        [
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/CORTE1.png',
                            'startMs' => 0,
                            'durationMs' => 100,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                        [
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/CORTE2.png',
                            'startMs' => 100,
                            'durationMs' => 200,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                        [
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/CORTE1.png',
                            'startMs' => 300,
                            'durationMs' => 200,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                         [
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/CORTE2.png',
                            'startMs' => 600,
                            'durationMs' => 170,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                        [
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/CORTE1.png',
                            'startMs' => 900,
                            'durationMs' => 200,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                    ],
                ],
                'Kamino Fuga' => [
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/sukunafuga1.png',
                            'durationMs' => 400,
                        ],
                        [
                            'sprite' => './sukunapasta/sprites/sukunafuga2.png',
                            'durationMs' => 400,
                        ],
                        [
                            'sprite' => './sukunapasta/sprites/FUGAFINAL.png',
                            'durationMs' => 1300,
                        ],
                        [
                            'sprite' => './sukunapasta/sprites/sukunabasefinal.png',
                            'durationMs' => 100,
                        ],
                    ],
                    'overlays' => [
                        [
                            'mode' => 'projectile',
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/FUGA.png',
                            'startMs' => 2200,
                            'durationMs' => 500,
                            'sizePx' => 280,
                            'frontOffsetPx' => 130,
                            'projectileAngleDeg' => -15,
                            'startOffsetX' => 0,
                            'startOffsetY' => -20,
                            'endOffsetX' => 0,
                            'endOffsetY' => 50,
                        ],
                    ],
                ],
                'Reverse Energy' => [
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/REALSUKUNAREGEN.png',
                            'durationMs' => 1500,
                        ],
                    ],
                ],
                'Domain' => [
                    'domainDelayMs' => 1200,
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/DOMAINSUKUNA.png',
                            'durationMs' => 9000,
                        ],
                    ],
                ],
            ],
            'reactions' => [
                'defendingHit' => [
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/sukunadefreal.png',
                            'durationMs' => 1200,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getRegeneracaoEnergia(): int {
        return self::REGENERACAO_PROPRIA;
    }
}

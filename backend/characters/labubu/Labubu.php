<?php

require_once __DIR__ . '/../../Personagem.php';

class Labubu extends Personagem {

    const CUSTO_MorangodoAmor = 600;
    const CUSTO_labuaura = 400;

    public function __construct(string $nome) {
        parent::__construct($nome, 300, 20, 2067);
    }

    public static function getDescricao(): string {
        return "Labubu (balanced HP, habilidades de bolha e cura, sprite base: LABUBU.png)";
    }

    public function MorangodoAmor(Personagem $alvo): string {
        $this->consumirEnergia(self::CUSTO_MorangodoAmor);

        $dano = $this->ataque * 4;
        $resultado = $this->executarAtaqueDireto($alvo, "MorangodoAmor", $dano);

        return $resultado['mensagem'];
    }

    public function labuaura(): string {
        $this->consumirEnergia(self::CUSTO_labuaura);

        $cura = 67;
        $this->curarVida($cura);

        return $this->formatarMensagemAcaoSemAlvo("labuaura (cura {$cura})");
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        return $this->MorangodoAmor($alvo);
    }

    public function getHabilidades(): array {
        return [
            ["nome" => "MorangodoAmor","metodo" => "MorangodoAmor","precisaAlvo" => true,  "energyCost" => self::CUSTO_MorangodoAmor],
            ["nome" => "labuaura",     "metodo" => "labuaura",    "precisaAlvo" => false, "energyCost" => self::CUSTO_labuaura]
        ];
    }

    public function getDescricoesAcoes(): array {
        return array_merge(parent::getDescricoesAcoes(), [
            'MorangodoAmor' => 'Causa dano moderado. Custo: ' . self::CUSTO_MorangodoAmor . ' energia.',
            'labuaura' => 'Cura aliado (sem alvo). Custo: ' . self::CUSTO_labuaura . ' energia.',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './assets/labubu/sprites/LABUBU.png',
            'selectSprite' => './assets/labubu/sprites/labubuicon.jpg',
            'winImage' => './assets/labubu/sprites/labubufarm.png',
            'actions' => [
                'Ataque' => [
                    'frames' => [
                        [
                            'sprite' => './assets/labubu/sprites/labubo.png',
                            'durationMs' => 450,
                        ],
                        [
                            'sprite' => './assets/labubu/sprites/labubo2.png',
                            'durationMs' => 350,
                        ],
                    ],
                ],
                'labuaura' => [
                    'frames' => [
                        [
                            'sprite' => './assets/labubu/sprites/AURALUTRUETRUE.png',
                            'durationMs' => 1900,
                        ],
                    ],
                ],
                'MorangodoAmor' => [
                    'frames' => [
                        [
                            'sprite' => './assets/labubu/sprites/LANCE.png',
                            'durationMs' => 800,
                        ],
                        [
                            'sprite' => './assets/labubu/sprites/MORANGOLABUBU.png',
                            'durationMs' => 600,
                        ],
                    ],
                    'overlays' => [
                        [
                            'mode' => 'projectile',
                            'target' => 'opponent',
                            'sprite' => './assets/labubu/sprites/AMORMORANGO.png',
                            'startMs' => 1100,
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
            ],
            'reactions' => [
                'defendingHit' => [
                    'frames' => [
                        [
                            'sprite' => './assets/labubu/sprites/DEFENDELABUBU.png',
                            'durationMs' => 1200,
                        ],
                    ],
                ],
            ],
        ];
    }
}

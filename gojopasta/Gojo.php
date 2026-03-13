<?php

require_once __DIR__ . '/../Personagem.php';

class Gojo extends Personagem {

    const CUSTO_INFINITO = 300;
    const CUSTO_REVERSE = 150;
    const CUSTO_VAZIO_ROXO = 200;
    const CUSTO_AZUL = 100;
    const REGENERACAO_PROPRIA = 50;

    public function __construct(string $nome) {
        parent::__construct($nome, 200, 20, 1000);
    }

    public static function getDescricao(): string {
        return "Gojo (HP alto, energia muito alta, habilidades: Azul, Vazio Roxo, Reverse Energy e Domain)";
    }

    public function vazioRoxo(Personagem $alvo): string {
        $this->consumirEnergia(self::CUSTO_VAZIO_ROXO);

        $danoReal = $this->ataque * 5; // ignora defesa
        $resultado = $this->executarAtaqueDireto($alvo, "Vazio Roxo", $danoReal);

        return $resultado['mensagem'];
    }

    public function azul(Personagem $alvo): string {
        $this->consumirEnergia(self::CUSTO_AZUL);

        $danoBase = max(0, $this->ataque);
        $danoReal = $danoBase * 2;
        $resultado = $this->executarAtaqueDireto($alvo, "Azul", $danoReal);

        return $resultado['mensagem'];
    }

    public function reverseEnergy(): string {
        $this->consumirEnergia(self::CUSTO_REVERSE);
        $this->curarVida(50);

        return $this->formatarMensagemAcaoSemAlvo("Reverse Energy");
    }

    public function infinityVoid(): string {
        $this->consumirEnergia(self::CUSTO_INFINITO);

        return $this->formatarMensagemAcaoSemAlvo("Domain");
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        return $this->vazioRoxo($alvo);
    }

    public function getHabilidades(): array {

        return [

            [
                "nome" => "Azul",
                "metodo" => "azul",
                "precisaAlvo" => true
            ],

            [
                "nome" => "Vazio Roxo",
                "metodo" => "vazioRoxo",
                "precisaAlvo" => true
            ],

            [
                "nome" => "Reverse Energy",
                "metodo" => "reverseEnergy",
                "precisaAlvo" => false
            ],

            [
                "nome" => "Domain",
                "metodo" => "infinityVoid",
                "precisaAlvo" => false
            ]

        ];
    }

    public function getDescricoesAcoes(): array {
        return array_merge(parent::getDescricoesAcoes(), [
            'Azul' => 'Causa 40 de dano. Custo: ' . self::CUSTO_AZUL . ' energia.',
            'Vazio Roxo' => 'Causa 100 de dano. Custo: ' . self::CUSTO_VAZIO_ROXO . ' energia.',
            'Reverse Energy' => 'Cura 50 de vida. Custo: ' . self::CUSTO_REVERSE . ' energia.',
            'Domain' => 'Impede o oponente de fazer qualquer ação durante 2 turnos. Custo: ' . self::CUSTO_INFINITO . ' energia.',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './gojopasta/sprites/GOJOBASEFINAL.png',
            'winImage' => './gojopasta/sprites/gojowin.jpg',
            'actions' => [
                'Ataque' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/sprites/gojopancadafinalmenor.png',
                            'durationMs' => 450,
                        ],
                        [
                            'sprite' => './gojopasta/sprites/gojohitpt2.png',
                            'durationMs' => 450,
                        ],
                    ],
                ],
                'Azul' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/sprites/gojoazulfinalreal.png',
                            'durationMs' => 1500,
                        ],
                                                [
                            'sprite' => './gojopasta/sprites/gojoblockrealreal.png',
                            'durationMs' => 200,
                        ],
                    ],
                    'overlays' => [
                        [
                            'target' => 'opponent',
                            'sprite' => './gojopasta/sprites/AZUL.png',
                            'startMs' => 1500,
                            'durationMs' => 1000,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                    ],
                ],
                'Vazio Roxo' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/sprites/GOJOROXOFASE1FINAL.png',
                            'durationMs' => 1000,
                        ],
                        [
                            'sprite' => './gojopasta/sprites/gojoROXOFINALFINALVERDADEIRO.png',
                            'durationMs' => 650,
                        ],
                        [
                            'sprite' => './gojopasta/sprites/gojoroxolast.png',
                            'durationMs' => 400,
                        ],
                    ],
                    'overlays' => [
                        [
                            'mode' => 'projectile',
                            'target' => 'opponent',
                            'sprite' => './gojopasta/sprites/ROXO.png',
                            'startMs' => 1700,
                            'durationMs' => 900,
                            'sizePx' => 280,
                            'frontOffsetPx' => 130,
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
                            'sprite' => './gojopasta/sprites/gojoreversofinal.png',
                            'durationMs' => 1500,
                        ],
                    ],
                ],
                'Domain' => [
                                        'domainDelayMs' => 1500,
                    'frames' => [
                        [
                            'sprite' => './gojopasta/sprites/gojodomainfinal.png',
                            'durationMs' => 2000,
                        ],
                    ],
                ],
            ],
            'reactions' => [
                'defendingHit' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/sprites/gojoblockrealreal.png',
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
<?php

require_once __DIR__ . '/../Personagem.php';
require_once __DIR__ . '/../ExcecaoJogo.php';

class Gojo extends Personagem {

    const CUSTO_INFINITO = 80;
    const CUSTO_REVERSE = 60;
    const CUSTO_VAZIO_ROXO = 40;
    const CUSTO_AZUL = 25;
    const REGENERACAO_PROPRIA = 35;

    public function __construct(string $nome) {
        parent::__construct($nome, 200, 15, 5, 1000);
    }

    public static function getDescricao(): string {
        return "Gojo (HP alto, energia muito alta, habilidades: Azul, Vazio Roxo, Reverse Energy e Domain)";
    }

    public function vazioRoxo(Personagem $alvo): string {

        if ($this->energiaAtual < self::CUSTO_VAZIO_ROXO) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_VAZIO_ROXO;

        $danoReal = $this->ataque * 5; // ignora defesa
        $vidaAntes = $alvo->getVidaAtual();

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Vazio Roxo", $alvo, $vidaAntes, $danoReal);
    }

    public function azul(Personagem $alvo): string {

        if ($this->energiaAtual < self::CUSTO_AZUL) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_AZUL;

        $danoBase = max(0, $this->ataque - $alvo->getDefesaTotal());

        $danoReal = $danoBase * 2;
        $vidaAntes = $alvo->getVidaAtual();

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("Azul", $alvo, $vidaAntes, $danoReal);
    }

    public function reverseEnergy(): string {

        if ($this->energiaAtual < self::CUSTO_REVERSE) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_REVERSE;

        $cura = 50;

        $this->vidaAtual += $cura;

        if ($this->vidaAtual > $this->vidaMaxima) {
            $this->vidaAtual = $this->vidaMaxima;
        }

        return $this->formatarMensagemAcaoSemAlvo("Reverse Energy");
    }

    public function infinityVoid(): string {

        if ($this->energiaAtual < self::CUSTO_INFINITO) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_INFINITO;

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
            'Azul' => "Causa 2x o dano base após defesa: (ataque {$this->ataque} - defesa do alvo) x 2. Custo: " . self::CUSTO_AZUL . ' energia.',
            'Vazio Roxo' => "Causa {$this->ataque} x 5 = " . ($this->ataque * 5) . ' de dano e ignora defesa. Custo: ' . self::CUSTO_VAZIO_ROXO . ' energia.',
            'Reverse Energy' => 'Cura 50 de vida imediatamente. Custo: ' . self::CUSTO_REVERSE . ' energia.',
            'Domain' => 'Ativa domínio, aplica pulo de turnos no inimigo e altera o cenário temporariamente. Custo: ' . self::CUSTO_INFINITO . ' energia.',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './gojopasta/GOJOBASEFINAL.png',
            'actions' => [
                'Ataque' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/gojopancadafinalmenor.png',
                            'durationMs' => 450,
                        ],
                        [
                            'sprite' => './gojopasta/gojohitpt2.png',
                            'durationMs' => 450,
                        ],
                    ],
                ],
                'Azul' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/gojoazulfinalreal.png',
                            'durationMs' => 2000,
                        ],
                    ],
                ],
                'Vazio Roxo' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/GOJOROXOFASE1FINAL.png',
                            'durationMs' => 1000,
                        ],
                        [
                            'sprite' => './gojopasta/gojoROXOFINALFINALVERDADEIRO.png',
                            'durationMs' => 1000,
                        ],
                    ],
                ],
                'Reverse Energy' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/gojoreversofinal.png',
                            'durationMs' => 1500,
                        ],
                    ],
                ],
                'Domain' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/gojodomainfinal.png',
                            'durationMs' => 2000,
                        ],
                    ],
                ],
            ],
            'reactions' => [
                'defendingHit' => [
                    'frames' => [
                        [
                            'sprite' => './gojopasta/gojoblockrealreal.png',
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
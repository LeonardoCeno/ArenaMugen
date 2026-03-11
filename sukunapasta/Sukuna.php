<?php

require_once __DIR__ . '/../Personagem.php';
require_once __DIR__ . '/../ExcecaoJogo.php';

class Sukuna extends Personagem {

    const CUSTO_DESMANTELAR = 250;
    const CUSTO_KAMINO_FUGA = 800;
    const CUSTO_DOMAIN = 1200;
    const CUSTO_REVERSE = 500;
    const REGENERACAO_PROPRIA = 70;

    public function __construct(string $nome) {
        parent::__construct($nome, 200, 25, 10, 4000);
    }

    public static function getDescricao(): string {
        return "Sukuna (Alto HP, ataque médio, habilidades: Desmantelar, Kamino Fuga, Reverse Energy e Domain)";
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        if ($this->energiaAtual < self::CUSTO_DESMANTELAR) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_DESMANTELAR;

        if ($alvo->tentouDesviarAtaque()) {
            return "{$this->nome} usou Desmantelar em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = max(0, $this->ataque - $alvo->getDefesaTotal());
        $alvo->receberDano($danoReal);

        $danoBleed = (int) ceil($danoReal * 0.40);
        if ($danoBleed > 0) {
            $alvo->aplicarSangramento($danoBleed, 1);
        }

        $mensagem = $this->formatarMensagemAcaoComAlvo("Desmantelar", $alvo, $vidaAntes, $danoReal);

        if ($danoBleed > 0) {
            $mensagem .= " Sangramento aplicado por 2 turnos ({$danoBleed} por turno).";
        }

        return $mensagem;
    }

    public function kaminoFuga(Personagem $alvo): string {
        if ($this->energiaAtual < self::CUSTO_KAMINO_FUGA) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_KAMINO_FUGA;

        if ($alvo->tentouDesviarAtaque()) {
            return "{$this->nome} usou Kamino Fuga em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = max(0, $this->ataque - $alvo->getDefesaTotal());
        $alvo->receberDano($danoReal);

        $danoBurn = (int) ceil($danoReal * 0.20);
        if ($danoBurn > 0) {
            $alvo->aplicarQueimadura($danoBurn, 1);
        }

        $mensagem = $this->formatarMensagemAcaoComAlvo("Kamino Fuga", $alvo, $vidaAntes, $danoReal);

        if ($danoBurn > 0) {
            $mensagem .= " Burn aplicado por 1 turno ({$danoBurn} por turno).";
        }

        return $mensagem;
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

    public function santuarioMalevolente(Personagem $alvo): string {
        if ($this->energiaAtual < self::CUSTO_DOMAIN) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_DOMAIN;

        if ($alvo->tentouDesviarAtaque()) {
            return "{$this->nome} usou Domain em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = max(0, $this->ataque - $alvo->getDefesaTotal());
        $alvo->receberDano($danoReal);

        $danoBleed = (int) ceil($danoReal * 0.50);
        if ($danoBleed > 0) {
            $alvo->aplicarSangramento($danoBleed, 4);
        }

        $mensagem = $this->formatarMensagemAcaoComAlvo("Domain", $alvo, $vidaAntes, $danoReal);

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
            'Desmantelar' => "Causa dano base: ataque {$this->ataque} - defesa do alvo. Aplica bleed por 1 turno com 40% do dano causado por turno. Custo: " . self::CUSTO_DESMANTELAR . ' energia.',
            'Kamino Fuga' => "Causa dano base: ataque {$this->ataque} - defesa do alvo. Aplica burn por 1 turno com 20% do dano causado por turno. Custo: " . self::CUSTO_KAMINO_FUGA . ' energia.',
            'Reverse Energy' => 'Cura 50 de vida imediatamente. Custo: ' . self::CUSTO_REVERSE . ' energia.',
            'Domain' => "Causa dano base: ataque {$this->ataque} - defesa do alvo. Aplica bleed por 4 turnos com 50% do dano causado por turno. Custo: " . self::CUSTO_DOMAIN . ' energia.',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './sukunapasta/sprites/sukunabasefinal.png',
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
                            'durationMs' => 500,
                            'x' => 0,
                            'y' => 0,
                            'scale' => 1,
                        ],
                        [
                            'target' => 'opponent',
                            'sprite' => './sukunapasta/sprites/CORTE2.png',
                            'startMs' => 500,
                            'durationMs' => 600,
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
                            'durationMs' => 300,
                        ],
                        [
                            'sprite' => './sukunapasta/sprites/sukunafuga2.png',
                            'durationMs' => 300,
                        ],
                        [
                            'sprite' => './sukunapasta/sprites/FUGAFINAL.png',
                            'durationMs' => 1200,
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
                    'frames' => [
                        [
                            'sprite' => './sukunapasta/sprites/DOMAINSUKUNA.png',
                            'durationMs' => 5000,
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

<?php

require_once __DIR__ . '/../Personagem.php';

class UbuntuKiller extends Personagem {

    const DANO_UBUNTUBUXA = 999;
    const REGENERACAO_PROPRIA = 0;

    public function __construct(string $nome) {
        parent::__construct($nome, 500, 100, 1000);
    }

    public static function getDescricao(): string {
        return 'UbuntuKiller (habilidade única: ubuntubuxa)';
    }

    public function receberDano(int $danoReal): void {
        $tipoDano = $this->consumirTipoDanoRecebido();
        $danoReal = $this->aplicarReducaoDanoDefesa($danoReal);

        if ($danoReal <= 0) {
            return;
        }

        if ($this->energiaAtual > 0) {
            $this->energiaAtual -= $danoReal;

            if ($this->energiaAtual < 0) {
                $this->energiaAtual = 0;
            }

            return;
        }

        $this->registrarTipoDanoRecebido($tipoDano);

        $this->vidaAtual -= $danoReal;

        if ($this->vidaAtual < 0) {
            $this->vidaAtual = 0;
        }
    }

    public function ubuntubuxa(Personagem $alvo): string {
        $resultado = $this->executarAtaqueDireto($alvo, 'ubuntubuxa', self::DANO_UBUNTUBUXA);

        return $resultado['mensagem'];
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        return $this->ubuntubuxa($alvo);
    }

    public function getHabilidades(): array {
        return [
            [
                'nome' => 'ubuntubuxa',
                'metodo' => 'ubuntubuxa',
                'precisaAlvo' => true,
            ],
        ];
    }

    public function getDescricoesAcoes(): array {
        return array_merge(parent::getDescricoesAcoes(), [
            'ubuntubuxa' => 'Causa 999 de dano.',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './ubuntukiller/sprites/UBUNTUKILLER.png',
            'winImage' => './ubuntukiller/sprites/EUWIN.png',
            'actions' => [
                'ubuntubuxa' => [
                    'frames' => [
                        [
                            'sprite' => './ubuntukiller/sprites/FOGO.png',
                            'durationMs' => 1800,
                        ],
                        [
                            'sprite' => './ubuntukiller/sprites/TIRO.png',
                            'durationMs' => 1100,
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

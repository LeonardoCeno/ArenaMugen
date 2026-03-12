<?php

require_once __DIR__ . '/../Personagem.php';
require_once __DIR__ . '/../ExcecaoJogo.php';

class Sans extends Personagem {

    const CUSTO_EEEEH = 0;
    const DANO_EEEEH = 999;
    const REGENERACAO_PROPRIA = 0;

    public function __construct(string $nome) {
        parent::__construct($nome, 1, 1, 0);
    }

    public static function getDescricao(): string {

        return "Sans (HP baixíssimo, ataque alto, passiva: esquiva usando energia, habilidade: eeeeh)";
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

    public function eeeeh(Personagem $alvo): string {
        if ($this->energiaAtual < self::CUSTO_EEEEH) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= self::CUSTO_EEEEH;

        if ($alvo->tentouDesviarAtaque()) {
            return "{$this->nome} usou eeeeh em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = self::DANO_EEEEH;

        $alvo->receberDano($danoReal);

        return $this->formatarMensagemAcaoComAlvo("eeeeh", $alvo, $vidaAntes, $danoReal);
    }

    public function usarHabilidadeEspecial(Personagem $alvo): string {
        return $this->eeeeh($alvo);
    }

    public function getHabilidades(): array {

        return [
            [
                "nome" => "eeeeh",
                "metodo" => "eeeeh",
                "precisaAlvo" => true
            ]
        ];
    }

    public function getDescricoesAcoes(): array {
        return array_merge(parent::getDescricoesAcoes(), [
            'eeeeh' => 'Causa 999 de dano fixo. Custo: ' . self::CUSTO_EEEEH . ' energia. (único que falto orçamento (tempo))',
        ]);
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => './sanspasta/sprites/SANSBASEFINAL.png',
            'winImage' => './sanspasta/sprites/sansrealista.jpg',
            'actions' => [
                'eeeeh' => [
                    'frames' => [
                        [
                            'sprite' => './sanspasta/sprites/SANSKILL1FINAL.png',
                            'durationMs' => 1000,
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
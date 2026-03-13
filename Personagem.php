<?php

require_once __DIR__ . '/ExcecaoJogo.php';

abstract class Personagem {

    protected string $nome;
    protected int $vidaMaxima;
    protected int $vidaAtual;
    protected int $ataque;
    protected int $energiaMaxima;
    protected int $energiaAtual;
    protected bool $defendendo = false;

    protected int $sangramentoTurnos = 0;
    protected int $sangramentoDanoPorTurno = 0;
    protected int $queimaduraTurnos = 0;
    protected int $queimaduraDanoPorTurno = 0;
    protected string $ultimoTipoDano = 'direct';
    protected ?string $proximoTipoDanoRecebido = null;

    const REGENERACAO_ENERGIA = 10;

    public function __construct(string $nome, int $vida, int $ataque, int $energia) {

        $this->nome = $nome;

        $this->vidaMaxima = $vida;
        $this->vidaAtual = $vida;

        $this->ataque = $ataque;

        $this->energiaMaxima = $energia;
        $this->energiaAtual = $energia;
    }

    public function getNome(): string {
        return $this->nome;
    }

    public function getVidaAtual(): int {
        return $this->vidaAtual;
    }

    public function getVidaMaxima(): int {
        return $this->vidaMaxima;
    }

    public function getEnergiaAtual(): int {
        return $this->energiaAtual;
    }

    public function getEnergiaMaxima(): int {
        return $this->energiaMaxima;
    }

    public function getUltimoTipoDano(): string {
        return $this->ultimoTipoDano;
    }

    public function estaDefendendo(): bool {
        return $this->defendendo;
    }

    public function estaVivo(): bool {
        return $this->vidaAtual > 0;
    }

    public function receberDano(int $danoReal): void {
        $tipoDano = $this->consumirTipoDanoRecebido();
        $danoReal = $this->aplicarReducaoDanoDefesa($danoReal);

        if ($danoReal <= 0) {
            return;
        }

        $this->registrarTipoDanoRecebido($tipoDano);

        $this->vidaAtual -= $danoReal;

        if ($this->vidaAtual < 0) {
            $this->vidaAtual = 0;
        }
    }

    public function regenerarEnergia(): void {

        $this->energiaAtual = min(
            $this->energiaMaxima,
            $this->energiaAtual + $this->getRegeneracaoEnergia()
        );
    }

    protected function getRegeneracaoEnergia(): int {
        return self::REGENERACAO_ENERGIA;
    }

    public function iniciarTurno(): void {
        $this->defendendo = false;
        $this->regenerarEnergia();
    }

    protected function aplicarReducaoDanoDefesa(int $danoReal): int {
        if ($danoReal <= 0) {
            return 0;
        }

        if (!$this->defendendo) {
            return $danoReal;
        }

        return (int) ceil($danoReal * 0.5);
    }

    public function processarEfeitosContinuosFimTurno(): void {
        $this->processarSangramento();
        $this->processarQueimadura();
    }

    public function atacar(Personagem $alvo): string {
        if ($alvo->tentouDesviarAtaque()) {
            return "{$this->nome} usou Ataque em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = max(0, $this->ataque);
        $foiCritico = $this->tentouCriticoAtaque();

        if ($foiCritico) {
                $danoReal = (int) ceil($danoReal * 2);
        }

        $alvo->receberDano($danoReal);

        $mensagem = $this->formatarMensagemAcaoComAlvo("Ataque", $alvo, $vidaAntes, $danoReal);

        if ($foiCritico && $danoReal > 0) {
            $mensagem .= " Acerto crítico!";
        }

        return $mensagem;
    }

    protected function tentouDesviarAtaque(): bool {
        return random_int(1, 100) <= 5;
    }

    protected function tentouCriticoAtaque(): bool {
        return random_int(1, 100) <= 5;
    }

    protected function formatarMensagemAcaoComAlvo(
        string $nomeAcao,
        Personagem $alvo,
        int $vidaAntes,
        int $danoReal
    ): string {
        $vidaDepois = $alvo->getVidaAtual();

        if ($vidaDepois === $vidaAntes || $danoReal <= 0) {
            return "{$this->nome} usou {$nomeAcao} em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!";
        }

        return "{$this->nome} usou {$nomeAcao} em {$alvo->getNome()}, causando {$danoReal} de dano.";
    }

    protected function formatarMensagemAcaoSemAlvo(string $nomeAcao): string {
        return "{$this->nome} usou {$nomeAcao}.";
    }

    public function defender(): string {
        $this->defendendo = true;
        return $this->formatarMensagemAcaoSemAlvo("Defesa");
    }

    protected function consumirEnergia(int $custo): void {
        if ($custo < 0) {
            $custo = 0;
        }

        if ($this->energiaAtual < $custo) {
            throw new EnergiaInsuficienteException();
        }

        $this->energiaAtual -= $custo;
    }

    protected function curarVida(int $cura): void {
        if ($cura <= 0) {
            return;
        }

        $this->vidaAtual += $cura;

        if ($this->vidaAtual > $this->vidaMaxima) {
            $this->vidaAtual = $this->vidaMaxima;
        }
    }

    protected function executarAtaqueDireto(Personagem $alvo, string $nomeAcao, int $dano): array {
        if ($alvo->tentouDesviarAtaque()) {
            return [
                'acertou' => false,
                'vidaAntes' => $alvo->getVidaAtual(),
                'danoReal' => 0,
                'foiCritico' => false,
                'mensagem' => "{$this->nome} usou {$nomeAcao} em {$alvo->getNome()}, mas {$alvo->getNome()} desviou!",
            ];
        }

        $vidaAntes = $alvo->getVidaAtual();
        $danoReal = max(0, $dano);
        $foiCritico = $this->tentouCriticoAtaque();

        if ($foiCritico) {
                $danoReal = (int) ceil($danoReal * 2);
        }

        $alvo->receberDano($danoReal);

        $mensagem = $this->formatarMensagemAcaoComAlvo($nomeAcao, $alvo, $vidaAntes, $danoReal);

        if ($foiCritico && $danoReal > 0) {
            $mensagem .= " Acerto crítico!";
        }

        return [
            'acertou' => true,
            'vidaAntes' => $vidaAntes,
            'danoReal' => $danoReal,
            'foiCritico' => $foiCritico,
            'mensagem' => $mensagem,
        ];
    }

    public function aplicarSangramento(int $danoPorTurno, int $turnos): void {
        $this->sangramentoDanoPorTurno = max(0, $danoPorTurno);
        $this->sangramentoTurnos = max(0, $turnos);
    }

    public function aplicarQueimadura(int $danoPorTurno, int $turnos): void {
        $this->queimaduraDanoPorTurno = max(0, $danoPorTurno);
        $this->queimaduraTurnos = max(0, $turnos);
    }

    protected function definirTipoDoProximoDanoRecebido(string $tipo): void {
        $this->proximoTipoDanoRecebido = $tipo;
    }

    protected function consumirTipoDanoRecebido(): string {
        $tipoDano = $this->proximoTipoDanoRecebido ?? 'direct';
        $this->proximoTipoDanoRecebido = null;

        return $tipoDano;
    }

    protected function registrarTipoDanoRecebido(string $tipo): void {
        $this->ultimoTipoDano = $tipo;
    }

    private function processarSangramento(): void {
        if ($this->sangramentoTurnos <= 0 || $this->sangramentoDanoPorTurno <= 0) {
            return;
        }

        $this->definirTipoDoProximoDanoRecebido('bleed');
        $this->receberDano($this->sangramentoDanoPorTurno);
        $this->sangramentoTurnos--;

        if ($this->sangramentoTurnos <= 0) {
            $this->sangramentoDanoPorTurno = 0;
        }
    }

    private function processarQueimadura(): void {
        if ($this->queimaduraTurnos <= 0 || $this->queimaduraDanoPorTurno <= 0) {
            return;
        }

        $this->definirTipoDoProximoDanoRecebido('burn');
        $this->receberDano($this->queimaduraDanoPorTurno);
        $this->queimaduraTurnos--;

        if ($this->queimaduraTurnos <= 0) {
            $this->queimaduraDanoPorTurno = 0;
        }
    }

    public function getHabilidades(): array {

        return [
            [
                "nome" => "Habilidade Especial",
                "metodo" => "usarHabilidadeEspecial",
                "precisaAlvo" => true
            ]
        ];
    }

    public function getDescricoesAcoes(): array {
        return [
            'Ataque' => "Causa {$this->ataque} de dano base.",
            'Defesa' => 'Reduz em 50% o dano recebido até o próximo turno.',
        ];
    }

    public function getConfiguracaoVisual(): array {
        return [
            'baseSprite' => null,
            'actions' => [],
            'reactions' => [],
        ];
    }

    abstract public function usarHabilidadeEspecial(Personagem $alvo): string;

    abstract public static function getDescricao(): string;
}
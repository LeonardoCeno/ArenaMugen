<?php

require_once 'Personagem.php';
require_once 'Guerreiro.php';
require_once 'Gojo.php';
require_once 'Sans.php';
require_once 'ExcecaoJogo.php';

function limparTela(): void {
    system('clear');
}

function exibirStatus(Personagem $p1, Personagem $p2, Personagem $atual, int $turno): void {

    echo "=== Turno de " . ($turno % 2 == 1 ? "Jogador 1" : "Jogador 2") . " ===\n";

    echo "Jogador 1: {$p1->getNome()} (HP: {$p1->getVidaAtual()}/{$p1->getVidaMaxima()}, Energia: {$p1->getEnergiaAtual()}/{$p1->getEnergiaMaxima()})\n";

    echo "Jogador 2: {$p2->getNome()} (HP: {$p2->getVidaAtual()}/{$p2->getVidaMaxima()}, Energia: {$p2->getEnergiaAtual()}/{$p2->getEnergiaMaxima()})\n";

    echo "\nAções disponíveis:\n";

    echo "1. Atacar\n";
    echo "2. Defender\n";

    $habilidades = $atual->getHabilidades();

    foreach ($habilidades as $i => $hab) {
        echo ($i + 3) . ". " . $hab["nome"] . "\n";
    }

    echo "Escolha uma ação: ";
}

function escolherPersonagem(int $jogador): Personagem {

    $personagens = [
        1 => Guerreiro::class,
        2 => Gojo::class,
        3 => Sans::class
    ];

    echo "Jogador {$jogador}, escolha seu personagem:\n";

    foreach ($personagens as $numero => $classe) {
        echo "{$numero}. " . $classe::getDescricao() . "\n";
    }

    do {

        echo "Escolha: ";

        $escolha = (int) trim(fgets(STDIN));

        if (!array_key_exists($escolha, $personagens)) {
            echo "Opção inválida. Escolha um personagem existente.\n";
        }

    } while (!array_key_exists($escolha, $personagens));

    echo "Digite o nome do personagem: ";

    $nome = trim(fgets(STDIN));

    $classe = $personagens[$escolha];

    return new $classe($nome);
}

function executarAcao(Personagem $atacante, Personagem $defensor, int $acao): string {

    if ($acao == 1) {
        return $atacante->atacar($defensor);
    }

    if ($acao == 2) {
        return $atacante->defender();
    }

    $habilidades = $atacante->getHabilidades();

    $index = $acao - 3;

    if (!isset($habilidades[$index])) {
        throw new EntradaInvalidaException();
    }

    $habilidade = $habilidades[$index];

    $metodo = $habilidade["metodo"];

    if ($habilidade["precisaAlvo"]) {
        return $atacante->$metodo($defensor);
    }

    return $atacante->$metodo();
}

function main(): void {

    do {

        limparTela();

        echo "Bem-vindo ao Jogo de Combate por Turnos!\n\n";

        $p1 = escolherPersonagem(1);
        $p2 = escolherPersonagem(2);

        $turno = 1;

        while ($p1->estaVivo() && $p2->estaVivo()) {

            limparTela();

            $atual = ($turno % 2 == 1) ? $p1 : $p2;
            $oponente = ($turno % 2 == 1) ? $p2 : $p1;

            exibirStatus($p1, $p2, $atual, $turno);

            try {

                $input = trim(fgets(STDIN));

                if (!is_numeric($input)) {
                    throw new EntradaInvalidaException();
                }

                $acao = (int)$input;

                $resultado = executarAcao($atual, $oponente, $acao);

                // AGORA o turno inicia após ação válida
                $atual->iniciarTurno();

                echo "\n{$resultado}\n";

            } catch (ExcecaoJogo $e) {

                echo "Erro: " . $e->getMessage() . "\n";
                continue;
            }

            echo "\nPressione Enter para continuar...";
            fgets(STDIN);

            $turno++;
        }

        limparTela();

        if (!$p1->estaVivo()) {
            echo "Jogador 2 venceu!\n";
        } else {
            echo "Jogador 1 venceu!\n";
        }

        echo "\nDeseja jogar novamente? (s/n): ";

        do {

            $resposta = strtolower(trim(fgets(STDIN)));

            if ($resposta !== 's' && $resposta !== 'n') {
                echo "Resposta inválida. Digite apenas (s/n): ";
            }

        } while ($resposta !== 's' && $resposta !== 'n');

    } while ($resposta === 's');

    echo "\nObrigado por jogar!\n";
}

main();
# Jogo de Combate por Turnos

Este é um protótipo de jogo de combate por turnos desenvolvido em PHP, rodando inteiramente no terminal. Dois jogadores controlam personagens em um duelo 1 contra 1, revezando turnos.

## Como Executar

Certifique-se de ter PHP 8.1 ou superior instalado. Execute o comando:

```
php index.php
```

## Personagens Disponíveis

### Guerreiro
- **Atributos**: Vida: 120, Ataque: 25, Defesa: 10, Energia: 80
- **Habilidade Especial**: Golpe Poderoso (custa 30 energia) - Causa dano dobrado ao oponente.

### Mago
- **Atributos**: Vida: 80, Ataque: 15, Defesa: 5, Energia: 120
- **Habilidade Especial**: Cura Mágica (custa 40 energia) - Recupera 30 HP do próprio personagem.

### Sans
- **Atributos**: Vida: 100, Ataque: 20, Defesa: 8, Energia: 100
- **Habilidade Especial**: Blaster (custa 50 energia) - Ataque mágico que causa dano triplo ao oponente.

## Regras do Jogo

- Cada jogador escolhe um personagem no início.
- Turnos alternados: Atacar, Defender ou Usar Habilidade Especial.
- O jogo termina quando um personagem fica com 0 HP ou menos.
- Energia regenera 10 pontos por turno.
- Defender aumenta temporariamente a defesa.

## Conceitos de POO Utilizados

- **Abstração**: Classe abstrata `Personagem`.
- **Herança**: `Guerreiro` e `Mago` herdam de `Personagem`.
- **Polimorfismo**: Métodos como `usarHabilidadeEspecial` são implementados diferentemente.
- **Encapsulamento**: Atributos protegidos, acesso via métodos.
- **Interface**: `AcaoCombate` (não utilizada diretamente, mas presente).
- **Tratamento de Exceções**: Exceções personalizadas para erros.
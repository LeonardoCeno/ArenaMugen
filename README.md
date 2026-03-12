# Jogo de Combate por Turnos (PHP)

Projeto de batalha por turnos com dois modos de execução:

- Terminal (CLI) via `index.php`
- Web via `batalha.html` + `web_api.php`

As regras de turno e ações foram centralizadas em `GameService.php` para evitar duplicação entre CLI e Web.

## Requisitos

- PHP 8.1+

## Executar no Terminal

```bash
php index.php
```

## Executar no Web

```bash
php -S 127.0.0.1:8080
```

Abra no navegador:

- `http://127.0.0.1:8080/batalha.html`

## Personagens

### Sukuna
- Vida 200, Ataque 25, Energia 4000
- Habilidades:
	- Desmantelar (bleed por 2 turnos, custo: 250)
	- Kamino Fuga (burn por 1 turno, custo: 800)
	- Reverse Energy (cura 50, custo: 500)
	- Domain (bleed por 4 turnos, custo: 1200)
- Regeneração por turno: 70

### Gojo
- Vida 200, Ataque 20, Energia 1000
- Habilidades: Azul (custo: 100), Vazio Roxo (custo: 200), Reverse Energy (custo: 150), Domain (custo: 300)
- Regeneração por turno: 50

### Sans
- Vida 1, Ataque 1, Energia 0
- Habilidade: eeeeh (999 de dano fixo, custo: 0)
- Regra especial: enquanto tiver energia, dano recebido é absorvido na energia
- Regeneração por turno: 0

### Ulquiorra
- Vida 240, Ataque 30, Energia 400
- Habilidades:
	- Cero (70 dano fixo, custo: 90)
	- cero oscuras (140 dano fixo, custo: 90)
	- Barrage (60 dano fixo + bleed por 2 turnos de 30% do dano da skill, custo: 75)
	- Heal (cura 100, custo: 170)
- Regeneração por turno: 40

## Estrutura

- `Personagem.php`: classe base e mecânicas comuns
- `sukunapasta/Sukuna.php`, `gojopasta/Gojo.php`, `sanspasta/Sans.php`: classes concretas
- `GameService.php`: fluxo central de partida (turno, ação, estado)
- `web_api.php`: camada HTTP/JSON para o front-end
- `index.php`: interface de terminal reutilizando `GameService.php`
- `batalha.html` / `batalha.css` / `app.js`: interface web
- `gojopasta/sprites`, `sukunapasta/sprites`, `sanspasta/sprites`, `ulquiorrapasta/sprites`: sprites e imagens usadas no front-end

## Observações

- As configurações visuais por personagem (sprite base e animações por ação) vêm das classes PHP e são enviadas pela API.
- O domínio do Sukuna utiliza `sukunapasta/sprites/santuario.jpeg` como fundo e pode exibir cortes visuais aleatórios na arena no modo Web.
- Para abrir o modo Web, use servidor local (`http://`), não `file://`.
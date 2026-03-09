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

### Guerreiro
- Vida 120, Ataque 25, Defesa 10, Energia 80
- Habilidade: Golpe Poderoso

### Gojo
- Vida 200, Ataque 15, Defesa 5, Energia 1000
- Habilidades: Azul, Vazio Roxo, Reverse Energy

### Sans
- Vida 1, Ataque 30, Defesa 1, Energia 200
- Habilidades: Blaster, Parede de Ossos
- Regra especial: enquanto tiver energia, dano recebido é absorvido na energia

## Estrutura

- `Personagem.php`: classe base e mecânicas comuns
- `Guerreiro.php`, `gojopasta/Gojo.php`, `sanspasta/Sans.php`: classes concretas
- `GameService.php`: fluxo central de partida (turno, ação, estado)
- `web_api.php`: camada HTTP/JSON para o front-end
- `index.php`: interface de terminal reutilizando `GameService.php`
- `batalha.html` / `batalha.css`: interface web

## Observações

- As configurações visuais por personagem (sprite base e animações por ação) vêm das classes PHP e são enviadas pela API.
- Para abrir o modo Web, use servidor local (`http://`), não `file://`.
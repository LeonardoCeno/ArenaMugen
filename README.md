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

## Estrutura

- `Personagem.php`: classe base e mecânicas comuns
- `sukunapasta/Sukuna.php`, `gojopasta/Gojo.php`, `sanspasta/Sans.php`: classes concretas
- `GameService.php`: fluxo central de partida (turno, ação, estado)
- `web_api.php`: camada HTTP/JSON para o front-end
- `index.php`: interface de terminal reutilizando `GameService.php`
- `batalha.html` / `batalha.css` / `app.js`: interface web

## Observações

- As configurações visuais por personagem (sprite base e animações por ação) vêm das classes PHP e são enviadas pela API.
- Para abrir o modo Web, use servidor local (`http://`), não `file://`.
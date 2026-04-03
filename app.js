(function () {
	const API_URL = "./web_api.php";
	const ACOES_POR_PAGINA = 3;
	const SPRITES_CORTES_DOMINIO_SUKUNA = [
		"./sukunapasta/sprites/CORTE1.png",
		"./sukunapasta/sprites/CORTE2.png",
		"./sukunapasta/sprites/cortedomain1.png",
		"./sukunapasta/sprites/cortedomain2.png",
	];
	const PLACEHOLDER_ACTIONS_HTML = `
		<button disabled>ATACAR</button>
		<button disabled>DEFENDER</button>
		<button class="pagination-btn" disabled>→</button>
		<button disabled>HABILIDADE</button>
	`;

	const state = {
		serverState: null,
		resolvendoAcao: false,
		actionPage: 0,
		anim: null,                      // { duration: number, cancel: fn } — toda animação ativa
		sprites: { p1: null, p2: null }, // sprites temporários dos fighters
		domainArenaClass: null,          // classe CSS ativa na arena (vem do PHP via domainArenaClass)
		domainCutsActive: false,         // se o efeito de cortes está ativo
		domainCutsIntervalId: null,
		domainCutsTimeouts: [],
	};

	const fighterPlayerEl = document.getElementById("fighter-player");
	const fighterEnemyEl = document.getElementById("fighter-enemy");

	const els = {
		turnInfo: document.getElementById("turn-info"),
		log: document.getElementById("battle-log"),
		combatFeed: document.getElementById("combat-feed"),
		skillPreview: document.getElementById("skill-preview"),
		skillPreviewTitle: document.getElementById("skill-preview-title"),
		skillPreviewText: document.getElementById("skill-preview-text"),
		menu: document.getElementById("action-menu"),
		arena: document.querySelector(".arena"),
		setupPanel: document.getElementById("setup-panel"),
		battleView: document.getElementById("battle-view"),
		startBtn: document.getElementById("start-btn"),
		p1Name: document.getElementById("p1-name"),
		p2Name: document.getElementById("p2-name"),
		p1Class: document.getElementById("p1-class"),
		p2Class: document.getElementById("p2-class"),
		winnerOverlay: document.getElementById("winner-overlay"),
		winnerSprite: document.getElementById("winner-sprite"),
		winnerText: document.getElementById("winner-text"),
		playAgainBtn: document.getElementById("play-again-btn"),
		cards: {
			enemy: {
				root: document.getElementById("card-enemy"),
				name: document.getElementById("enemy-name"),
				tag: document.getElementById("enemy-tag"),
				hpText: document.getElementById("enemy-hp-text"),
				energyText: document.getElementById("enemy-energy-text"),
				hpBar: document.getElementById("enemy-hp-bar"),
				energyBar: document.getElementById("enemy-energy-bar"),
			},
			player: {
				root: document.getElementById("card-player"),
				name: document.getElementById("player-name"),
				tag: document.getElementById("player-tag"),
				hpText: document.getElementById("player-hp-text"),
				energyText: document.getElementById("player-energy-text"),
				hpBar: document.getElementById("player-hp-bar"),
				energyBar: document.getElementById("player-energy-bar"),
			},
		},
		fighters: {
			p1: {
				root: fighterPlayerEl,
				img: fighterPlayerEl?.querySelector(".fighter-img") || null,
				initial: fighterPlayerEl?.querySelector("span") || null,
			},
			p2: {
				root: fighterEnemyEl,
				img: fighterEnemyEl?.querySelector(".fighter-img") || null,
				initial: fighterEnemyEl?.querySelector("span") || null,
			},
		},
	};

	// =========================================================
	// API
	// =========================================================

	async function chamarApi(action, payload = {}) {
		const response = await fetch(API_URL, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ action, ...payload }),
		});

		if (!response.ok) {
			let mensagemErro = `Falha na API (${response.status}).`;
			try {
				const corpoErro = await response.json();
				if (corpoErro && corpoErro.message) {
					mensagemErro = corpoErro.message;
				}
			} catch (_) {}
			throw new Error(mensagemErro);
		}

		return response.json();
	}

	// =========================================================
	// Utilitários gerais
	// =========================================================

	function percentual(atual, maximo) {
		if (maximo <= 0) return "0%";
		return `${Math.max(0, Math.min(100, (atual / maximo) * 100))}%`;
	}

	function classePerigosa(hpAtual, hpMax) {
		return hpMax > 0 && hpAtual / hpMax <= 0.3;
	}

	function adicionarLog(texto) {
		const li = document.createElement("li");
		li.textContent = texto;
		els.log.prepend(li);

		while (els.log.children.length > 8) {
			els.log.removeChild(els.log.lastChild);
		}
	}

	function obterDescricaoAcao(acao) {
		if (acao && typeof acao.description === "string" && acao.description.trim() !== "") {
			return acao.description;
		}
		return "Ação de combate sem descrição detalhada.";
	}

	function mostrarPreviewSkill(nomeAcao, descricao) {
		if (!els.combatFeed || !els.skillPreviewTitle || !els.skillPreviewText) return;
		els.skillPreviewTitle.textContent = nomeAcao;
		els.skillPreviewText.textContent = descricao;
		els.combatFeed.classList.add("previewing-skill");
	}

	function esconderPreviewSkill() {
		if (!els.combatFeed) return;
		els.combatFeed.classList.remove("previewing-skill");
	}

	function normalizarTipoFlutuante(tipo) {
		if (tipo === "bleed" || tipo === "burn" || tipo === "heal") return tipo;
		return "direct";
	}

	function obterFighterRootPorChave(chaveJogador) {
		return chaveJogador === "p1" ? els.fighters.p1.root : els.fighters.p2.root;
	}

	function atualizarClasseFlipDoFighter(fighterEl) {
		if (!fighterEl) return;
		fighterEl.classList.toggle("is-flipped", fighterEl.dataset.side === "player2");
	}

	function mostrarNumeroFlutuante(chaveJogador, valor, tipo = "direct", foiCritico = false) {
		if (valor <= 0) return;

		const fighter = obterFighterRootPorChave(chaveJogador);
		if (!fighter) return;

		const damageEl = document.createElement("div");
		const tipoNormalizado = normalizarTipoFlutuante(tipo);
		damageEl.className = `damage-float damage-${tipoNormalizado}`;
		if (foiCritico && tipoNormalizado !== "heal") {
			damageEl.classList.add("damage-float-critical");
		}
		damageEl.textContent = tipoNormalizado === "heal" ? `+${valor}` : `-${valor}`;
		fighter.appendChild(damageEl);

		requestAnimationFrame(() => damageEl.classList.add("show"));
		setTimeout(() => damageEl.remove(), 1250);
	}

	function aplicarNovoEstado(novoEstado, mostrarDano = false) {
		const estadoAnterior = state.serverState;
		state.serverState = novoEstado;

		if (!mostrarDano || !estadoAnterior?.started || !novoEstado?.started) return;

		const danoP1 = Math.max(0, (estadoAnterior.p1?.vidaAtual ?? 0) - (novoEstado.p1?.vidaAtual ?? 0));
		const danoP2 = Math.max(0, (estadoAnterior.p2?.vidaAtual ?? 0) - (novoEstado.p2?.vidaAtual ?? 0));
		const curaP1 = Math.max(0, (novoEstado.p1?.vidaAtual ?? 0) - (estadoAnterior.p1?.vidaAtual ?? 0));
		const curaP2 = Math.max(0, (novoEstado.p2?.vidaAtual ?? 0) - (estadoAnterior.p2?.vidaAtual ?? 0));
		const tipoDanoP1 = novoEstado.p1?.ultimoTipoDano || "direct";
		const tipoDanoP2 = novoEstado.p2?.ultimoTipoDano || "direct";
		const mensagemAcao = (novoEstado?.message || "").toString();
		const teveCritico = mensagemAcao.includes("Acerto crítico!");
		const nomeP1 = (novoEstado?.p1?.nome || "").toString();
		const nomeP2 = (novoEstado?.p2?.nome || "").toString();

		let criticoP1 = false;
		let criticoP2 = false;

		if (teveCritico) {
			if (nomeP1 && mensagemAcao.includes(`em ${nomeP1}`) && danoP1 > 0) criticoP1 = true;
			if (nomeP2 && mensagemAcao.includes(`em ${nomeP2}`) && danoP2 > 0) criticoP2 = true;
			if (!criticoP1 && !criticoP2) {
				if (danoP1 > 0 && danoP2 <= 0) criticoP1 = true;
				else if (danoP2 > 0 && danoP1 <= 0) criticoP2 = true;
			}
		}

		mostrarNumeroFlutuante("p1", danoP1, tipoDanoP1, criticoP1);
		mostrarNumeroFlutuante("p2", danoP2, tipoDanoP2, criticoP2);
		mostrarNumeroFlutuante("p1", curaP1, "heal");
		mostrarNumeroFlutuante("p2", curaP2, "heal");
	}

	function esperar(ms) {
		if (ms <= 0) return Promise.resolve();
		return new Promise((resolve) => setTimeout(resolve, ms));
	}

	function mostrarSplashErroInsano(src, duracaoMs = 3000) {
		return new Promise((resolve) => {
			const overlay = document.createElement("div");
			overlay.style.cssText = "position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center";

			const img = document.createElement("img");
			img.src = src;
			img.alt = "ERRO INSANO";
			img.style.cssText = "max-width:95vw;max-height:95vh;width:auto;height:auto";

			overlay.appendChild(img);
			document.body.appendChild(overlay);
			setTimeout(() => { overlay.remove(); resolve(); }, duracaoMs);
		});
	}

	// =========================================================
	// Núcleo de animação — Timeline
	// =========================================================

	// Recebe lista de { at: ms, run: fn } e agenda todos via setTimeout.
	// Retorna { duration, cancel } — cancel() limpa todos os timers pendentes.
	function runTimeline(events) {
		if (!events.length) return { duration: 0, cancel() {} };
		const handles = events.map(({ at, run }) => setTimeout(run, at));
		const duration = events.reduce((max, e) => Math.max(max, e.at), 0);
		return {
			duration,
			cancel() { handles.forEach(clearTimeout); },
		};
	}

	// Cancela animação em andamento e limpa todo estado visual temporário.
	// Substitui: limparTimersAnimacao, limparOverlaysFighter, limparOverlaysArena,
	//            limparTodosTimersAnimacao, limparPreviewDominio, limparSpritesTemporarios.
	function cancelAnimation() {
		state.anim?.cancel();
		state.anim = null;
		state.sprites.p1 = null;
		state.sprites.p2 = null;
		state.domainArenaClass = null;
		state.domainCutsActive = false;
		limparCortesDominioSukuna();
		document.querySelectorAll(".arena-action-overlay, .arena-energy-beam, .fighter-action-overlay")
			.forEach((el) => el.remove());
	}

	// =========================================================
	// Construtores de eventos de animação
	// =========================================================

	// Transforma um array de frames em eventos de timeline para um jogador.
	// Cada frame troca o sprite temporário e chama atualizarHUD.
	function buildFrameEvents(chaveJogador, frames) {
		if (!frames.length) return [];
		const events = [];
		let t = 0;
		for (const frame of frames) {
			const sprite = frame.sprite;
			events.push({ at: t, run() { state.sprites[chaveJogador] = sprite; atualizarHUD(); } });
			t += frame.durationMs;
		}
		// Sentinel: marca o fim real da sequência para runTimeline calcular a duração correta.
		// Sem isso, duration = at do último frame (início), não at + durationMs (fim).
		events.push({ at: t, run() {} });
		return events;
	}

	// Calcula posições e ângulos para projéteis e beams entre dois fighters.
	function calcularPosicoes(overlay, atacanteKey, origemEl, alvoEl, arenaRect) {
		const direcaoFrente = atacanteKey === "p1" ? 1 : -1;
		const escalaHorizontal = atacanteKey === "p2" ? -1 : 1;
		const anguloProjetil = atacanteKey === "p2" ? -overlay.projectileAngleDeg : overlay.projectileAngleDeg;
		const origemRect = origemEl.getBoundingClientRect();
		const alvoRect = alvoEl.getBoundingClientRect();
		const origemX = (origemRect.left + origemRect.width / 2) - arenaRect.left + direcaoFrente * overlay.frontOffsetPx + overlay.startOffsetX;
		const origemY = (origemRect.top + origemRect.height / 2) - arenaRect.top + overlay.startOffsetY;
		const alvoX = (alvoRect.left + alvoRect.width / 2) - arenaRect.left + overlay.endOffsetX;
		const alvoY = (alvoRect.top + alvoRect.height / 2) - arenaRect.top + overlay.endOffsetY;
		const deltaX = alvoX - origemX;
		const deltaY = alvoY - origemY;
		return {
			origemX, origemY, alvoX, alvoY,
			distancia: Math.sqrt(deltaX * deltaX + deltaY * deltaY),
			anguloAuto: Math.atan2(deltaY, deltaX) * (180 / Math.PI),
			anguloProjetil, escalaHorizontal,
		};
	}

	// Cada criador recebe apenas o que precisa — sem setup duplicado.

	function criarBeamEl(overlay, pos) {
		const el = document.createElement("div");
		el.className = "arena-energy-beam";
		if (overlay.beamTone === "dark") el.classList.add("arena-energy-beam-dark");
		else if (overlay.beamTone === "pink") el.classList.add("arena-energy-beam-pink");
		el.setAttribute("aria-hidden", "true");
		el.style.cssText = `left:${pos.origemX}px;top:${pos.origemY}px;height:${overlay.thicknessPx}px;width:0px;transform:translate(0,-50%) rotate(${pos.anguloAuto}deg);transition:width ${overlay.durationMs}ms ease-out`;
		els.arena.appendChild(el);
		requestAnimationFrame(() => { el.style.width = `${pos.distancia}px`; });
		return el;
	}

	function criarProjectileEl(overlay, pos) {
		const el = document.createElement("img");
		el.className = "arena-action-overlay";
		el.src = overlay.sprite;
		el.alt = "";
		el.setAttribute("aria-hidden", "true");
		el.style.cssText = `width:${overlay.sizePx}px;left:${pos.origemX}px;top:${pos.origemY}px;transform:translate(-50%,-50%) scaleX(${pos.escalaHorizontal}) rotate(${pos.anguloProjetil}deg);transition:left ${overlay.durationMs}ms linear,top ${overlay.durationMs}ms linear`;
		els.arena.appendChild(el);
		requestAnimationFrame(() => { el.style.left = `${pos.alvoX}px`; el.style.top = `${pos.alvoY}px`; });
		return el;
	}

	function criarAttachedEl(overlay, alvoKey) {
		const fighter = els.fighters[alvoKey]?.root;
		if (!fighter) return null;
		const el = document.createElement("img");
		el.className = "fighter-action-overlay";
		el.src = overlay.sprite;
		el.alt = "";
		el.setAttribute("aria-hidden", "true");
		el.style.transform = `translate(${overlay.x}px,${overlay.y}px) scale(${overlay.scale})`;
		fighter.appendChild(el);
		return el;
	}

	// Ponto central de criação: resolve alvo, computa geometria (uma única vez para
	// beam/projectile) e delega ao criador correto. Cada criador só faz DOM.
	function criarOverlayEl(overlay, atacanteKey) {
		const alvoKey = overlay.target === "self" ? atacanteKey : (atacanteKey === "p1" ? "p2" : "p1");

		if (overlay.mode !== "beam" && overlay.mode !== "projectile") {
			return criarAttachedEl(overlay, alvoKey);
		}

		const arenaRect = els.arena?.getBoundingClientRect();
		const origemEl = els.fighters[atacanteKey]?.root;
		const alvoEl   = els.fighters[alvoKey]?.root;
		if (!arenaRect || !origemEl || !alvoEl) return null;

		const pos = calcularPosicoes(overlay, atacanteKey, origemEl, alvoEl, arenaRect);
		if (overlay.mode === "beam") return criarBeamEl(overlay, pos);
		return criarProjectileEl(overlay, pos);
	}

	// Gera par de eventos [criar, remover] para um overlay na timeline.
	function buildOverlayEvents(overlay, atacanteKey) {
		let el = null;
		return [
			{ at: overlay.startMs, run() { el = criarOverlayEl(overlay, atacanteKey); } },
			{ at: overlay.startMs + overlay.durationMs, run() { el?.remove(); el = null; } },
		];
	}

	// Gera eventos de domain a partir do config PHP — sem hardcode de personagens.
	// Ativado por qualquer ação que declare 'domainArenaClass' no seu visual config.
	function buildDomainEvents(atacanteKey, nomeAcao) {
		const actionConfig = state.serverState?.[atacanteKey]?.visual?.actions?.[nomeAcao] ?? {};
		const arenaClass = actionConfig.domainArenaClass ?? null;
		if (!arenaClass) return [];

		const delay = Number(actionConfig.domainDelayMs) > 0 ? Number(actionConfig.domainDelayMs) : 0;
		const cutsDelay = Number(actionConfig.domainCutsDelayMs) > 0 ? Number(actionConfig.domainCutsDelayMs) : null;

		const events = [
			{ at: delay, run() { state.domainArenaClass = arenaClass; atualizarHUD(); } },
		];

		if (cutsDelay !== null) {
			events.push({
				at: delay + cutsDelay,
				run() { state.domainCutsActive = true; atualizarHUD(); },
			});
		}

		return events;
	}

	// Monta toda a timeline de uma ação: frames do atacante, reação do defensor,
	// overlays e efeitos de domain. Retorna o array de eventos para runTimeline.
	function buildAnimation(atacanteKey, acao, defensorKey, defensorEstaDefendendo) {
		const nomeAcao = acao.nomeSprite || acao.nome;
		const events = [];

		events.push(...buildFrameEvents(atacanteKey, obterFramesAnimacaoAcao(atacanteKey, nomeAcao)));

		if (acao.targetsOpponent && defensorEstaDefendendo) {
			events.push(...buildFrameEvents(defensorKey, obterFramesReacaoDefesa(defensorKey)));
		}

		for (const overlay of obterOverlaysAnimacaoAcao(atacanteKey, nomeAcao)) {
			events.push(...buildOverlayEvents(overlay, atacanteKey));
		}

		events.push(...buildDomainEvents(atacanteKey, nomeAcao));

		return events;
	}

	// =========================================================
	// Cortes do Domain do Sukuna (setInterval próprio)
	// =========================================================

	function obterLayerCortesDominio() {
		if (!els.arena) return null;
		let layer = els.arena.querySelector(".domain-cuts-layer");
		if (!layer) {
			layer = document.createElement("div");
			layer.className = "domain-cuts-layer";
			els.arena.appendChild(layer);
		}
		return layer;
	}

	function limparCortesDominioSukuna() {
		state.domainCutsTimeouts.forEach((id) => clearTimeout(id));
		state.domainCutsTimeouts = [];

		if (state.domainCutsIntervalId !== null) {
			clearInterval(state.domainCutsIntervalId);
			state.domainCutsIntervalId = null;
		}

		const layer = els.arena?.querySelector(".domain-cuts-layer");
		if (layer) layer.innerHTML = "";
	}

	function criarCorteAleatorioDominioSukuna() {
		const layer = obterLayerCortesDominio();
		if (!layer || !SPRITES_CORTES_DOMINIO_SUKUNA.length) return;

		const sprite = SPRITES_CORTES_DOMINIO_SUKUNA[Math.floor(Math.random() * SPRITES_CORTES_DOMINIO_SUKUNA.length)];
		const corte = document.createElement("img");
		corte.className = "domain-cut";
		corte.src = sprite;
		corte.alt = "";
		corte.setAttribute("aria-hidden", "true");
		corte.style.left = `${Math.random() * 100}%`;
		corte.style.top = `${Math.random() * 100}%`;
		corte.style.width = "500px";
		corte.style.transform = `translate(-50%, -50%) rotate(${Math.floor(Math.random() * 360)}deg)`;
		layer.appendChild(corte);

		const timeoutId = setTimeout(() => corte.remove(), 260 + Math.floor(Math.random() * 240));
		state.domainCutsTimeouts.push(timeoutId);
	}

	function atualizarEfeitoCortesDominioSukuna(ativo) {
		if (!ativo) {
			limparCortesDominioSukuna();
			return;
		}
		if (state.domainCutsIntervalId !== null) return;

		criarCorteAleatorioDominioSukuna();
		state.domainCutsIntervalId = setInterval(() => {
			const quantidade = 1 + Math.floor(Math.random() * 2);
			for (let i = 0; i < quantidade; i++) criarCorteAleatorioDominioSukuna();
		}, 30);
	}

	// =========================================================
	// Renderização visual
	// =========================================================

	function aplicarVisualPersonagem(chaveJogador, personagem, fighterRefs) {
		if (!fighterRefs?.root || !fighterRefs.img) return;

		const spriteTemporario = state.sprites[chaveJogador];
		const nomeClasse = personagem?.classe || "";
		const spriteBase = personagem?.visual?.baseSprite || null;
		const fighterEl = fighterRefs.root;
		const img = fighterRefs.img;
		const initial = fighterRefs.initial;

		fighterEl.classList.remove("has-image", "is-flipped", "action-casting", "true-cero-sized", "true-cero-plus-sized", "ubuntu-base-smaller");
		img.removeAttribute("src");

		if (spriteTemporario) {
			img.src = spriteTemporario;
			fighterEl.classList.add("has-image", "action-casting");
			if (spriteTemporario.endsWith("/ciferfinalform.png") || spriteTemporario.endsWith("/ciferfinalcero.png")) {
				fighterEl.classList.add("true-cero-sized");
			}
			if (spriteTemporario.endsWith("/ciferfinalcero.png")) {
				fighterEl.classList.add("true-cero-plus-sized");
			}
			atualizarClasseFlipDoFighter(fighterEl);
			img.onerror = () => fighterEl.classList.remove("has-image", "is-flipped", "action-casting");
			if (initial) initial.textContent = (nomeClasse || "?").trim().charAt(0).toUpperCase() || "?";
			return;
		}

		if (spriteBase) {
			img.src = spriteBase;
			fighterEl.classList.add("has-image");
			if (nomeClasse.toLowerCase() === "ubuntu") fighterEl.classList.add("ubuntu-base-smaller");
			atualizarClasseFlipDoFighter(fighterEl);
		}

		img.onerror = () => fighterEl.classList.remove("has-image", "is-flipped");
		if (initial) initial.textContent = (personagem?.classeNome || "?").trim().charAt(0).toUpperCase() || "?";
	}

	// Troca a classe de domain da arena sem precisar saber quais classes existem.
	// Usa dataset.domainClass para rastrear o que foi aplicado e remover na troca.
	function setArenaDomain(arenaClass) {
		const atual = els.arena?.dataset.domainClass || null;
		if (atual === arenaClass) return;
		if (atual) els.arena.classList.remove(atual);
		if (arenaClass) {
			els.arena.classList.add(arenaClass);
			els.arena.dataset.domainClass = arenaClass;
		} else {
			delete els.arena.dataset.domainClass;
		}
	}

	function atualizarCardStatus(personagem, refs) {
		refs.name.textContent = personagem.classeNome.toUpperCase();
		refs.tag.textContent = personagem.nome;
		refs.hpText.textContent = `${personagem.vidaAtual} / ${personagem.vidaMaxima}`;
		refs.energyText.textContent = `${personagem.energiaAtual} / ${personagem.energiaMaxima}`;
		refs.hpBar.style.width = percentual(personagem.vidaAtual, personagem.vidaMaxima);
		refs.energyBar.style.width = percentual(personagem.energiaAtual, personagem.energiaMaxima);
		refs.hpBar.classList.toggle("danger", classePerigosa(personagem.vidaAtual, personagem.vidaMaxima));
	}

	function mostrarTelaVitoria(server) {
		if (!server || !server.winner) {
			els.winnerOverlay.classList.add("hidden");
			return;
		}
		const vencedor = server.winner === "p1" ? server.p1 : server.p2;
		const labelVencedor = server.winner === "p1" ? "Jogador 1" : "Jogador 2";
		const spriteVitoria = vencedor?.visual?.winImage || vencedor?.visual?.baseSprite || "";

		els.winnerText.textContent = `${labelVencedor} (${vencedor.nome}) venceu!`;
		if (spriteVitoria) {
			els.winnerSprite.src = spriteVitoria;
			els.winnerSprite.style.display = "block";
		} else {
			els.winnerSprite.removeAttribute("src");
			els.winnerSprite.style.display = "none";
		}
		els.winnerOverlay.classList.remove("hidden");
	}

	function atualizarHUD() {
		const server = state.serverState;
		if (!server || !server.started) {
			setArenaDomain(null);
			atualizarEfeitoCortesDominioSukuna(false);
			els.winnerOverlay.classList.add("hidden");
			return;
		}

		// Classe ativa: preview tem prioridade; se não houver, usa o domain server-ativo.
		// A classe vem do PHP (visual.actions.Domain.domainArenaClass) — sem hardcode de personagens.
		let domainClass = state.domainArenaClass;
		if (!domainClass && (server.domainTurnsRemaining || 0) > 0 && server.domainCasterKey) {
			domainClass = server[server.domainCasterKey]?.visual?.actions?.["Domain"]?.domainArenaClass ?? null;
		}
		setArenaDomain(domainClass);
		atualizarEfeitoCortesDominioSukuna(state.domainCutsActive);

		atualizarCardStatus(server.p2, els.cards.enemy);
		atualizarCardStatus(server.p1, els.cards.player);
		aplicarVisualPersonagem("p2", server.p2, els.fighters.p2);
		aplicarVisualPersonagem("p1", server.p1, els.fighters.p1);

		if (server.winner) {
			els.turnInfo.textContent = `${server.winner === "p1" ? "Jogador 1" : "Jogador 2"} venceu!`;
			mostrarTelaVitoria(server);
			return;
		}

		els.winnerOverlay.classList.add("hidden");
		const jogadorDaVez = server.currentKey === "p1" ? "Jogador 1" : "Jogador 2";
		const nomeDaVez = server.currentKey === "p1" ? server.p1.nome : server.p2.nome;
		els.turnInfo.textContent = `Turno ${server.turno} • ${jogadorDaVez} (${nomeDaVez})`;
	}

	// =========================================================
	// Leitura de dados de animação do serverState
	// =========================================================

	function obterFramesAnimacao(chaveJogador, caminho, nome) {
		const server = state.serverState;
		if (!server || !server[chaveJogador]) return [];

		const raiz = caminho === "actions"
			? server[chaveJogador].visual?.actions || {}
			: server[chaveJogador].visual?.reactions || {};

		const alvo = raiz[nome];
		const frames = Array.isArray(alvo?.frames) ? alvo.frames : [];
		return frames
			.filter((f) => f && typeof f.sprite === "string" && f.sprite.trim() !== "")
			.map((f) => ({
				sprite: f.sprite,
				durationMs: Number(f.durationMs) > 0 ? Number(f.durationMs) : 0,
			}));
	}

	function obterFramesAnimacaoAcao(chaveJogador, nomeAcao) {
		return obterFramesAnimacao(chaveJogador, "actions", nomeAcao);
	}

	function obterOverlaysAnimacaoAcao(chaveJogador, nomeAcao) {
		const server = state.serverState;
		if (!server || !server[chaveJogador]) return [];

		const actionConfig = server[chaveJogador].visual?.actions?.[nomeAcao];
		const overlays = Array.isArray(actionConfig?.overlays) ? actionConfig.overlays : [];

		return overlays
			.filter((o) => {
				if (!o) return false;
				if (o.mode === "beam") return true;
				return typeof o.sprite === "string" && o.sprite.trim() !== "";
			})
			.map((o) => ({
				mode: o.mode === "projectile" ? "projectile" : (o.mode === "beam" ? "beam" : "attached"),
				beamTone: o.beamTone === "dark" ? "dark" : (o.beamTone === "pink" ? "pink" : "normal"),
				target: o.target === "self" ? "self" : "opponent",
				sprite: typeof o.sprite === "string" ? o.sprite : "",
				startMs: Number(o.startMs) > 0 ? Number(o.startMs) : 0,
				durationMs: Number(o.durationMs) > 0 ? Number(o.durationMs) : 0,
				x: Number.isFinite(Number(o.x)) ? Number(o.x) : 0,
				y: Number.isFinite(Number(o.y)) ? Number(o.y) : 0,
				scale: Number(o.scale) > 0 ? Number(o.scale) : 1,
				sizePx: Number(o.sizePx) > 0 ? Number(o.sizePx) : 260,
				frontOffsetPx: Number.isFinite(Number(o.frontOffsetPx)) ? Number(o.frontOffsetPx) : 0,
				projectileAngleDeg: Number.isFinite(Number(o.projectileAngleDeg)) ? Number(o.projectileAngleDeg) : 0,
				thicknessPx: Number(o.thicknessPx) > 0 ? Number(o.thicknessPx) : 26,
				startOffsetX: Number.isFinite(Number(o.startOffsetX)) ? Number(o.startOffsetX) : 0,
				startOffsetY: Number.isFinite(Number(o.startOffsetY)) ? Number(o.startOffsetY) : 0,
				endOffsetX: Number.isFinite(Number(o.endOffsetX)) ? Number(o.endOffsetX) : 0,
				endOffsetY: Number.isFinite(Number(o.endOffsetY)) ? Number(o.endOffsetY) : 0,
			}));
	}

	function obterFramesReacaoDefesa(chaveJogador) {
		return obterFramesAnimacao(chaveJogador, "reactions", "defendingHit");
	}


	// =========================================================
	// Menu de ações
	// =========================================================

	function setBotoesAcaoHabilitados(habilitado) {
		Array.from(els.menu.querySelectorAll("button")).forEach((btn) => {
			if (btn.classList.contains("pagination-btn")) {
				const totalAcoes = (state.serverState?.availableActions || []).length;
				const totalPaginas = Math.max(1, Math.ceil(totalAcoes / ACOES_POR_PAGINA));
				btn.disabled = !habilitado || totalPaginas <= 1;
				return;
			}
			if (btn.textContent.trim() !== "-") btn.disabled = !habilitado;
		});
	}

	function montarAcoes() {
		els.menu.innerHTML = "";
		const server = state.serverState;
		if (!server || !server.started || server.winner) return;

		const acoes = (server.availableActions || []).map((acao) => ({
			...acao,
			nome: acao.label,
			nomeSprite: acao.skillName || acao.label,
		}));

		const totalPaginas = Math.max(1, Math.ceil(acoes.length / ACOES_POR_PAGINA));
		if (state.actionPage >= totalPaginas) state.actionPage = 0;

		const inicio = state.actionPage * ACOES_POR_PAGINA;
		const acoesPagina = acoes.slice(inicio, inicio + ACOES_POR_PAGINA);
		while (acoesPagina.length < ACOES_POR_PAGINA) acoesPagina.push({ nome: "-", type: null });

		// Layout: [acao0] [acao1] [→] [acao2]
		const slots = [acoesPagina[0], acoesPagina[1], null, acoesPagina[2]];
		slots.forEach((acao, slotIndex) => {
			const btn = document.createElement("button");

			if (slotIndex === 2) {
				btn.textContent = "→";
				btn.classList.add("pagination-btn");
				if (totalPaginas <= 1 || state.resolvendoAcao) {
					btn.disabled = true;
				} else {
					btn.addEventListener("click", () => {
						state.actionPage = (state.actionPage + 1) % totalPaginas;
						montarAcoes();
					});
				}
				els.menu.appendChild(btn);
				return;
			}

			btn.textContent = acao.nome;
			if (!acao.type) {
				btn.disabled = true;
			} else {
				const nomeAcao = acao.nomeSprite || acao.nome;
				const descricaoAcao = obterDescricaoAcao(acao);
				btn.addEventListener("mouseenter", () => mostrarPreviewSkill(nomeAcao, descricaoAcao));
				btn.addEventListener("mouseleave", esconderPreviewSkill);
				btn.addEventListener("focus", () => mostrarPreviewSkill(nomeAcao, descricaoAcao));
				btn.addEventListener("blur", esconderPreviewSkill);
				btn.addEventListener("click", () => processarAcaoComAnimacao(acao));
			}

			els.menu.appendChild(btn);
		});
	}

	// =========================================================
	// Animações pós-resposta
	// =========================================================

	function animarEsquiva(chaveJogador) {
		const fighter = obterFighterRootPorChave(chaveJogador);
		if (!fighter) return;

		// dodgeSprite vem do PHP via visual.dodgeSprite — elimina o hardcode de classe
		const dodgeSprite = state.serverState?.[chaveJogador]?.visual?.dodgeSprite ?? null;
		const spriteAnterior = state.sprites[chaveJogador];

		if (dodgeSprite) {
			state.sprites[chaveJogador] = dodgeSprite;
			atualizarHUD();
		}

		fighter.classList.remove("dodge-anim");
		void fighter.offsetWidth; // força reflow para reiniciar a animação CSS
		fighter.classList.add("dodge-anim");

		setTimeout(() => {
			fighter.classList.remove("dodge-anim");
			if (dodgeSprite && state.sprites[chaveJogador] === dodgeSprite) {
				state.sprites[chaveJogador] = spriteAnterior;
				atualizarHUD();
			}
		}, 1200);
	}

	// =========================================================
	// Loop principal de turno
	// =========================================================

	async function processarAcaoComAnimacao(acao) {
		if (state.resolvendoAcao || !state.serverState?.started || state.serverState.winner) return;

		esconderPreviewSkill();
		state.resolvendoAcao = true;
		setBotoesAcaoHabilitados(false);

		const atacanteKey = state.serverState.currentKey;
		const defensorKey = atacanteKey === "p1" ? "p2" : "p1";
		const defensorEstaDefendendo = state.serverState[defensorKey]?.defendendo === true;
		// errorSplash lido aqui pois serverState muda após a resposta da API
		const errorSplash = state.serverState[atacanteKey]?.visual?.errorSplash ?? null;

		cancelAnimation();
		state.anim = runTimeline(buildAnimation(atacanteKey, acao, defensorKey, defensorEstaDefendendo));

		try {
			await esperar(state.anim.duration);

			const resposta = await chamarApi("action", {
				actionType: acao.type,
				skillIndex: typeof acao.skillIndex === "number" ? acao.skillIndex : null,
			});

			cancelAnimation();
			const mensagem = resposta.message || "Ação executada.";

			if (resposta.state?.started === false) {
				if (errorSplash) await mostrarSplashErroInsano(errorSplash, 3000);
				resetarParaSetup();
				adicionarLog(mensagem);
				return;
			}

			if (resposta.state) {
				aplicarNovoEstado(resposta.state, true);
				if (mensagem.includes("desviou!") && acao.targetsOpponent) {
					animarEsquiva(defensorKey);
				}
			}

			adicionarLog(mensagem);
			atualizarHUD();
		} catch (erro) {
			cancelAnimation();
			atualizarHUD();
			adicionarLog(`Erro ao executar ação: ${erro.message || "falha desconhecida."}`);
		} finally {
			state.resolvendoAcao = false;
			state.actionPage = 0;
			montarAcoes();
			setBotoesAcaoHabilitados(true);
		}
	}

	// =========================================================
	// Ciclo de vida do app
	// =========================================================

	function resetarParaSetup() {
		state.serverState = null;
		state.resolvendoAcao = false;
		state.actionPage = 0;
		cancelAnimation();
		esconderPreviewSkill();

		els.battleView.classList.add("hidden");
		els.setupPanel.classList.remove("hidden");
		els.winnerOverlay.classList.add("hidden");
		setArenaDomain(null);

		els.turnInfo.textContent = "Prepare a partida";
		els.log.innerHTML = "";
		adicionarLog("Configure os jogadores e clique em INICIAR BATALHA.");
		els.menu.innerHTML = PLACEHOLDER_ACTIONS_HTML;
	}

	async function iniciar() {
		if (window.location.protocol === "file:") {
			adicionarLog("Abra pelo servidor PHP: http://127.0.0.1:8080/batalha.html");
			return;
		}

		els.startBtn.disabled = true;
		try {
			const resposta = await chamarApi("start", {
				p1Name: els.p1Name.value.trim() || "Jogador 1",
				p1Class: els.p1Class.value,
				p2Name: els.p2Name.value.trim() || "Jogador 2",
				p2Class: els.p2Class.value,
			});

			if (!resposta.ok) {
				adicionarLog(resposta.message || "Não foi possível iniciar a partida.");
				return;
			}

			aplicarNovoEstado(resposta.state, false);
			state.resolvendoAcao = false;
			state.actionPage = 0;
			cancelAnimation();
			esconderPreviewSkill();

			els.setupPanel.classList.add("hidden");
			els.battleView.classList.remove("hidden");
			els.log.innerHTML = "";
			adicionarLog(`Partida iniciada: ${state.serverState.p1.classeNome} vs ${state.serverState.p2.classeNome}.`);
			atualizarHUD();
			montarAcoes();
		} catch (erro) {
			adicionarLog(`Erro ao iniciar: ${erro.message || "falha de conexão com a API."}`);
			adicionarLog("Confirme se o servidor está rodando em http://127.0.0.1:8080");
		} finally {
			els.startBtn.disabled = false;
		}
	}

	els.startBtn.addEventListener("click", iniciar);
	els.playAgainBtn.addEventListener("click", resetarParaSetup);
	adicionarLog("Configure os jogadores e clique em INICIAR BATALHA.");
})();

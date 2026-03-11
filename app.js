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
		domainPreviewType: null,
		animationTimers: {
			player1: [],
			player2: [],
		},
		spriteTemporario: {
			player1: null,
			player2: null,
		},
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
		if (!els.combatFeed || !els.skillPreviewTitle || !els.skillPreviewText) {
			return;
		}

		els.skillPreviewTitle.textContent = nomeAcao;
		els.skillPreviewText.textContent = descricao;
		els.combatFeed.classList.add("previewing-skill");
	}

	function esconderPreviewSkill() {
		if (!els.combatFeed) {
			return;
		}

		els.combatFeed.classList.remove("previewing-skill");
	}

	function normalizarTipoDano(tipoDano) {
		if (tipoDano === "bleed" || tipoDano === "burn") {
			return tipoDano;
		}

		return "direct";
	}

	function mostrarNumeroDano(chaveJogador, dano, tipoDano = "direct") {
		if (dano <= 0) {
			return;
		}

		const fighter = chaveJogador === "p1" ? els.fighters.p1.root : els.fighters.p2.root;
		if (!fighter) {
			return;
		}

		const damageEl = document.createElement("div");
		damageEl.className = `damage-float damage-${normalizarTipoDano(tipoDano)}`;
		damageEl.textContent = `-${dano}`;
		fighter.appendChild(damageEl);

		requestAnimationFrame(() => {
			damageEl.classList.add("show");
		});

		setTimeout(() => {
			damageEl.remove();
		}, 1250);
	}

	function aplicarNovoEstado(novoEstado, mostrarDano = false) {
		const estadoAnterior = state.serverState;
		state.serverState = novoEstado;

		if (!mostrarDano || !estadoAnterior?.started || !novoEstado?.started) {
			return;
		}

		const danoP1 = Math.max(0, (estadoAnterior.p1?.vidaAtual ?? 0) - (novoEstado.p1?.vidaAtual ?? 0));
		const danoP2 = Math.max(0, (estadoAnterior.p2?.vidaAtual ?? 0) - (novoEstado.p2?.vidaAtual ?? 0));
		const tipoDanoP1 = novoEstado.p1?.ultimoTipoDano || "direct";
		const tipoDanoP2 = novoEstado.p2?.ultimoTipoDano || "direct";

		mostrarNumeroDano("p1", danoP1, tipoDanoP1);
		mostrarNumeroDano("p2", danoP2, tipoDanoP2);
	}

	function limparSpritesTemporarios() {
		state.spriteTemporario.player1 = null;
		state.spriteTemporario.player2 = null;
	}

	function limparOverlaysFighter(lado) {
		const fighter = lado === "player1" ? els.fighters.p1.root : els.fighters.p2.root;
		if (!fighter) {
			return;
		}

		fighter.querySelectorAll(".fighter-action-overlay").forEach((el) => el.remove());
	}

	function limparTimersAnimacao(lado) {
		const timers = state.animationTimers[lado] || [];
		timers.forEach((timerId) => clearTimeout(timerId));
		state.animationTimers[lado] = [];
		limparOverlaysFighter(lado);
	}

	function limparTodosTimersAnimacao() {
		limparTimersAnimacao("player1");
		limparTimersAnimacao("player2");
	}

	function esperar(ms) {
		if (ms <= 0) {
			return Promise.resolve();
		}

		return new Promise((resolve) => {
			setTimeout(resolve, ms);
		});
	}

	function obterLayerCortesDominio() {
		if (!els.arena) {
			return null;
		}

		let layer = els.arena.querySelector(".domain-cuts-layer");
		if (!layer) {
			layer = document.createElement("div");
			layer.className = "domain-cuts-layer";
			els.arena.appendChild(layer);
		}

		return layer;
	}

	function limparCortesDominioSukuna() {
		state.domainCutsTimeouts.forEach((timerId) => clearTimeout(timerId));
		state.domainCutsTimeouts = [];

		if (state.domainCutsIntervalId !== null) {
			clearInterval(state.domainCutsIntervalId);
			state.domainCutsIntervalId = null;
		}

		const layer = els.arena?.querySelector(".domain-cuts-layer");
		if (layer) {
			layer.innerHTML = "";
		}
	}

	function criarCorteAleatorioDominioSukuna() {
		const layer = obterLayerCortesDominio();
		if (!layer || !SPRITES_CORTES_DOMINIO_SUKUNA.length) {
			return;
		}

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

		const timeoutId = setTimeout(() => {
			corte.remove();
		}, 260 + Math.floor(Math.random() * 240));

		state.domainCutsTimeouts.push(timeoutId);
	}

	function atualizarEfeitoCortesDominioSukuna(ativo) {
		if (!ativo) {
			limparCortesDominioSukuna();
			return;
		}

		if (state.domainCutsIntervalId !== null) {
			return;
		}

		criarCorteAleatorioDominioSukuna();
		state.domainCutsIntervalId = setInterval(() => {
			const quantidade = 1 + Math.floor(Math.random() * 2);
			for (let i = 0; i < quantidade; i++) {
				criarCorteAleatorioDominioSukuna();
			}
		}, 30);
	}

	function aplicarVisualPersonagem(personagem, fighterRefs, spriteTemporario = null) {
		if (!fighterRefs?.root || !fighterRefs.img) {
			return;
		}

		const nomeClasse = personagem?.classe || "";
		const spriteBase = personagem?.visual?.baseSprite || null;
		const fighterEl = fighterRefs.root;
		const img = fighterRefs.img;
		const initial = fighterRefs.initial;

		fighterEl.classList.remove("has-image", "is-flipped");
		fighterEl.classList.remove("action-casting");
		img.removeAttribute("src");

		if (spriteTemporario) {
			img.src = spriteTemporario;
			fighterEl.classList.add("has-image", "action-casting");

			if (fighterEl.dataset.side === "player2") {
				fighterEl.classList.add("is-flipped");
			}

			img.onerror = () => fighterEl.classList.remove("has-image", "is-flipped", "action-casting");
			if (initial) {
				initial.textContent = (nomeClasse || "?").trim().charAt(0).toUpperCase() || "?";
			}
			return;
		}

		if (spriteBase) {
			img.src = spriteBase;
			fighterEl.classList.add("has-image");

			if (fighterEl.dataset.side === "player2") {
				fighterEl.classList.add("is-flipped");
			}
		}

		img.onerror = () => fighterEl.classList.remove("has-image", "is-flipped");
		if (initial) {
			initial.textContent = (personagem?.classeNome || "?").trim().charAt(0).toUpperCase() || "?";
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
		const spriteBase = vencedor?.visual?.baseSprite || "";

		els.winnerText.textContent = `${labelVencedor} (${vencedor.nome}) venceu!`;

		if (spriteBase) {
			els.winnerSprite.src = spriteBase;
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
			els.arena.classList.remove("domain-active");
			els.arena.classList.remove("sukuna-domain-active");
			atualizarEfeitoCortesDominioSukuna(false);
			els.winnerOverlay.classList.add("hidden");
			return;
		}

		const previewSukunaAtivo = state.domainPreviewType === "sukuna";
		const previewGojoAtivo = state.domainPreviewType === "gojo";
		const dominioGojoAtivo = !previewSukunaAtivo && (previewGojoAtivo || (server.domainTurnsRemaining || 0) > 0);

		els.arena.classList.toggle("domain-active", dominioGojoAtivo);
		els.arena.classList.toggle("sukuna-domain-active", previewSukunaAtivo);
		atualizarEfeitoCortesDominioSukuna(previewSukunaAtivo);

		atualizarCardStatus(server.p2, els.cards.enemy);
		atualizarCardStatus(server.p1, els.cards.player);

		aplicarVisualPersonagem(server.p2, els.fighters.p2, state.spriteTemporario.player2);
		aplicarVisualPersonagem(server.p1, els.fighters.p1, state.spriteTemporario.player1);

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

	function obterChaveLado(chaveJogador) {
		return chaveJogador === "p1" ? "player1" : "player2";
	}

	function obterFramesAnimacao(chaveJogador, caminho, nome) {
		const server = state.serverState;
		if (!server || !server[chaveJogador]) {
			return [];
		}

		const raiz = caminho === "actions"
			? server[chaveJogador].visual?.actions || {}
			: server[chaveJogador].visual?.reactions || {};

		const alvo = raiz[nome];
		const frames = Array.isArray(alvo?.frames) ? alvo.frames : [];

		return frames
			.filter((frame) => frame && typeof frame.sprite === "string" && frame.sprite.trim() !== "")
			.map((frame) => ({
				sprite: frame.sprite,
				durationMs: Number(frame.durationMs) > 0 ? Number(frame.durationMs) : 0,
			}));
	}

	function obterFramesAnimacaoAcao(chaveJogador, nomeAcao) {
		return obterFramesAnimacao(chaveJogador, "actions", nomeAcao);
	}

	function obterOverlaysAnimacaoAcao(chaveJogador, nomeAcao) {
		const server = state.serverState;
		if (!server || !server[chaveJogador]) {
			return [];
		}

		const actionConfig = server[chaveJogador].visual?.actions?.[nomeAcao];
		const overlays = Array.isArray(actionConfig?.overlays) ? actionConfig.overlays : [];

		return overlays
			.filter((overlay) => overlay && typeof overlay.sprite === "string" && overlay.sprite.trim() !== "")
			.map((overlay) => ({
				target: overlay.target === "self" ? "self" : "opponent",
				sprite: overlay.sprite,
				startMs: Number(overlay.startMs) > 0 ? Number(overlay.startMs) : 0,
				durationMs: Number(overlay.durationMs) > 0 ? Number(overlay.durationMs) : 0,
				x: Number.isFinite(Number(overlay.x)) ? Number(overlay.x) : 0,
				y: Number.isFinite(Number(overlay.y)) ? Number(overlay.y) : 0,
				scale: Number(overlay.scale) > 0 ? Number(overlay.scale) : 1,
			}));
	}

	function obterFramesReacaoDefesa(chaveJogador) {
		return obterFramesAnimacao(chaveJogador, "reactions", "defendingHit");
	}

	function obterTipoPreviewDominio(chaveJogador, nomeAcao) {
		if (nomeAcao !== "Domain") {
			return null;
		}

		const classe = (state.serverState?.[chaveJogador]?.classe || "").toLowerCase();
		if (classe === "gojo") {
			return "gojo";
		}

		if (classe === "sukuna") {
			return "sukuna";
		}

		return null;
	}

	function executarAnimacaoFrames(lado, frames, duracaoPadrao = 0) {
		limparTimersAnimacao(lado);

		if (!frames.length) {
			return duracaoPadrao;
		}

		let acumulado = 0;
		frames.forEach((frame) => {
			const inicioFrame = acumulado;
			const timerId = setTimeout(() => {
				state.spriteTemporario[lado] = frame.sprite;
				atualizarHUD();
			}, inicioFrame);
			state.animationTimers[lado].push(timerId);
			acumulado += frame.durationMs;
		});

		return acumulado > 0 ? acumulado : duracaoPadrao;
	}

	function executarOverlaysAnimacao(atacanteKey, overlays) {
		if (!Array.isArray(overlays) || overlays.length === 0) {
			return 0;
		}

		let tempoTotal = 0;

		overlays.forEach((overlay) => {
			const alvoKey = overlay.target === "self"
				? atacanteKey
				: (atacanteKey === "p1" ? "p2" : "p1");
			const ladoAlvo = obterChaveLado(alvoKey);
			const fighter = ladoAlvo === "player1" ? els.fighters.p1.root : els.fighters.p2.root;

			if (!fighter) {
				return;
			}

			const inicio = overlay.startMs;
			const duracao = overlay.durationMs;
			tempoTotal = Math.max(tempoTotal, inicio + duracao);

			const timerInicio = setTimeout(() => {
				const overlayEl = document.createElement("img");
				overlayEl.className = "fighter-action-overlay";
				overlayEl.src = overlay.sprite;
				overlayEl.alt = "";
				overlayEl.setAttribute("aria-hidden", "true");
				overlayEl.style.transform = `translate(${overlay.x}px, ${overlay.y}px) scale(${overlay.scale})`;
				fighter.appendChild(overlayEl);

				const timerFim = setTimeout(() => {
					overlayEl.remove();
				}, duracao);

				state.animationTimers[ladoAlvo].push(timerFim);
			}, inicio);

			state.animationTimers[ladoAlvo].push(timerInicio);
		});

		return tempoTotal;
	}

	function setBotoesAcaoHabilitados(habilitado) {
		Array.from(els.menu.querySelectorAll("button")).forEach((btn) => {
			if (btn.classList.contains("pagination-btn")) {
				const totalAcoes = (state.serverState?.availableActions || []).length;
				const totalPaginas = Math.max(1, Math.ceil(totalAcoes / ACOES_POR_PAGINA));
				btn.disabled = !habilitado || totalPaginas <= 1;
				return;
			}

			if (btn.textContent.trim() !== "-") {
				btn.disabled = !habilitado;
			}
		});
	}

	function montarAcoes() {
		els.menu.innerHTML = "";
		const server = state.serverState;

		if (!server || !server.started || server.winner) {
			return;
		}

		const acoes = (server.availableActions || []).map((acao) => ({
			...acao,
			nome: acao.label,
			nomeSprite: acao.skillName || acao.label,
		}));

		const totalPaginas = Math.max(1, Math.ceil(acoes.length / ACOES_POR_PAGINA));
		if (state.actionPage >= totalPaginas) {
			state.actionPage = 0;
		}

		const inicio = state.actionPage * ACOES_POR_PAGINA;
		const acoesPagina = acoes.slice(inicio, inicio + ACOES_POR_PAGINA);
		while (acoesPagina.length < ACOES_POR_PAGINA) {
			acoesPagina.push({ nome: "-", type: null });
		}

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

	function obterElementoFighter(chaveJogador) {
		return els.fighters[chaveJogador]?.root || null;
	}

	function animarEsquiva(chaveJogador) {
		const fighter = obterElementoFighter(chaveJogador);
		if (!fighter) return;

		fighter.classList.remove("dodge-anim");
		void fighter.offsetWidth;
		fighter.classList.add("dodge-anim");

		setTimeout(() => {
			fighter.classList.remove("dodge-anim");
		}, 1200);
	}

	async function processarAcaoComAnimacao(acao) {
		if (state.resolvendoAcao || !state.serverState || !state.serverState.started || state.serverState.winner) {
			return;
		}

		esconderPreviewSkill();

		const atacanteKey = state.serverState.currentKey;
		const defensorKey = atacanteKey === "p1" ? "p2" : "p1";
		const ladoAtacante = obterChaveLado(atacanteKey);
		const ladoDefensor = obterChaveLado(defensorKey);
		const nomeAcao = acao.nomeSprite || acao.nome;
		const tipoPreviewDominio = obterTipoPreviewDominio(atacanteKey, nomeAcao);
		const framesAnimacao = obterFramesAnimacaoAcao(atacanteKey, nomeAcao);
		const overlaysAnimacao = obterOverlaysAnimacaoAcao(atacanteKey, nomeAcao);
		const defensorEstaDefendendo = state.serverState[defensorKey]?.defendendo === true;
		const framesReacaoDefesa = acao.targetsOpponent && defensorEstaDefendendo
			? obterFramesReacaoDefesa(defensorKey)
			: [];

		state.resolvendoAcao = true;
		setBotoesAcaoHabilitados(false);

		if (tipoPreviewDominio) {
			state.domainPreviewType = tipoPreviewDominio;
			atualizarHUD();
		}

		const duracaoAtaque = executarAnimacaoFrames(ladoAtacante, framesAnimacao, 0);
		const duracaoDefesa = executarAnimacaoFrames(ladoDefensor, framesReacaoDefesa, 0);
		const duracaoOverlays = executarOverlaysAnimacao(atacanteKey, overlaysAnimacao);
		const tempoResolucao = Math.max(duracaoAtaque, duracaoDefesa, duracaoOverlays);

		try {
			await esperar(tempoResolucao);

			const resposta = await chamarApi("action", {
				actionType: acao.type,
				skillIndex: typeof acao.skillIndex === "number" ? acao.skillIndex : null,
			});

			limparSpritesTemporarios();
			if (resposta.state) {
				aplicarNovoEstado(resposta.state, true);
			}

			state.domainPreviewType = null;
			const mensagem = resposta.message || "Ação executada.";
			adicionarLog(mensagem);
			atualizarHUD();

			if (mensagem.includes("desviou!") && (acao.type === "attack" || acao.type === "skill")) {
				animarEsquiva(atacanteKey === "p1" ? "p2" : "p1");
			}
		} catch (erro) {
			limparSpritesTemporarios();
			state.domainPreviewType = null;
			atualizarHUD();
			adicionarLog(`Erro ao executar ação: ${erro.message || "falha desconhecida."}`);
		} finally {
			state.resolvendoAcao = false;
			state.actionPage = 0;
			montarAcoes();
			setBotoesAcaoHabilitados(true);
		}
	}

	function resetarParaSetup() {
		state.serverState = null;
		state.resolvendoAcao = false;
		state.actionPage = 0;
		state.domainPreviewType = null;
		limparTodosTimersAnimacao();
		limparCortesDominioSukuna();
		limparSpritesTemporarios();
		esconderPreviewSkill();

		els.battleView.classList.add("hidden");
		els.setupPanel.classList.remove("hidden");
		els.winnerOverlay.classList.add("hidden");
		els.arena.classList.remove("domain-active");
		els.arena.classList.remove("sukuna-domain-active");

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
			state.domainPreviewType = null;
			limparTodosTimersAnimacao();
			limparSpritesTemporarios();
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

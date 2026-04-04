import { createUIController } from "./ui-status.js";
import { createAnimationController } from "./battle-animations.js";

(() => {
	const API_URL = "../backend/web_api.php";

	const state = {
		serverState: null,
		resolvendoAcao: false,
		actionPage: 0,
		anim: null,
		sprites: { p1: null, p2: null },
		domainImage: null,
		domainCutsActive: false,
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

	let ui = null;
	let animations = null;

	const atualizarHUD = () => {
		if (!ui || !animations) return;
		ui.atualizarHUD({
			renderFighter: animations.aplicarVisualPersonagem,
			updateDomainCuts: animations.atualizarEfeitoCortesDominioSukuna,
		});
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

	function aplicarNovoEstado(novoEstado, mostrarDano = false) {
		const estadoAnterior = state.serverState;
		state.serverState = novoEstado;

		if (!mostrarDano || !estadoAnterior?.started || !novoEstado?.started) return;
		animations.aplicarFeedbackDeDano(estadoAnterior, novoEstado);
	}

	async function processarAcaoComAnimacao(acao) {
		if (state.resolvendoAcao || !state.serverState?.started || state.serverState.winner) return;

		ui.esconderPreviewSkill();
		state.resolvendoAcao = true;
		ui.setBotoesAcaoHabilitados(false);

		const atacanteKey = state.serverState.currentKey;
		const defensorKey = atacanteKey === "p1" ? "p2" : "p1";
		const defensorEstaDefendendo = state.serverState[defensorKey]?.defendendo === true;
		const errorSplash = state.serverState[atacanteKey]?.visual?.errorSplash ?? null;

		animations.cancelAnimation();
		const animacaoAtiva = animations.runTimeline(
			animations.buildAnimation(atacanteKey, acao, defensorKey, defensorEstaDefendendo)
		);
		state.anim = animacaoAtiva;

		try {
			await animations.esperar(animacaoAtiva.duration);

			const resposta = await chamarApi("action", {
				actionType: acao.type,
				skillIndex: typeof acao.skillIndex === "number" ? acao.skillIndex : null,
			});

			animations.cancelAnimation();
			const mensagem = resposta.message || "Ação executada.";

			if (resposta.state?.started === false) {
				if (errorSplash) await animations.mostrarSplashErroInsano(errorSplash, 3000);
				resetarParaSetup();
				ui.adicionarLog(mensagem);
				return;
			}

			if (resposta.state) {
				aplicarNovoEstado(resposta.state, true);
				if (resposta.state.lastActionDodged && acao.targetsOpponent) {
					animations.animarEsquiva(defensorKey);
				}
			}

			ui.adicionarLog(mensagem);
			atualizarHUD();
		} catch (erro) {
			animations.cancelAnimation();
			atualizarHUD();
			ui.adicionarLog(`Erro ao executar ação: ${erro.message || "falha desconhecida."}`);
		} finally {
			state.resolvendoAcao = false;
			state.actionPage = 0;
			ui.montarAcoes();
			ui.setBotoesAcaoHabilitados(true);
		}
	}

	function resetarParaSetup() {
		ui.resetarParaSetup(animations.cancelAnimation);
	}

	async function iniciar() {
		if (window.location.protocol === "file:") {
			ui.adicionarLog("Abra pelo servidor PHP: http://127.0.0.1:8080/batalha.html");
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
				ui.adicionarLog(resposta.message || "Não foi possível iniciar a partida.");
				return;
			}

			aplicarNovoEstado(resposta.state, false);
			state.resolvendoAcao = false;
			state.actionPage = 0;
			animations.cancelAnimation();
			ui.esconderPreviewSkill();

			els.setupPanel.classList.add("is-hidden");
			els.battleView.classList.remove("is-hidden");
			els.log.innerHTML = "";
			ui.adicionarLog(`Partida iniciada: ${state.serverState.p1.classeNome} vs ${state.serverState.p2.classeNome}.`);
			atualizarHUD();
			ui.montarAcoes();
		} catch (erro) {
			ui.adicionarLog(`Erro ao iniciar: ${erro.message || "falha de conexão com a API."}`);
			ui.adicionarLog("Confirme se o servidor está rodando em http://127.0.0.1:8080");
		} finally {
			els.startBtn.disabled = false;
		}
	}

	animations = createAnimationController({ state, els, atualizarHUD });
	ui = createUIController({
		state,
		els,
		onActionSelected: processarAcaoComAnimacao,
	});

	els.startBtn.addEventListener("click", iniciar);
	els.playAgainBtn.addEventListener("click", resetarParaSetup);
	ui.adicionarLog("Configure os jogadores e clique em INICIAR BATALHA.");
})();

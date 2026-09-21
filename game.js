const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const multiplierDisplay = document.getElementById('multiplierDisplay');
const balanceDisplay = document.getElementById('balanceDisplay');
const betInput = document.getElementById('betAmount');
const actionBtn = document.getElementById('actionBtn');
const gameMessage = document.getElementById('gameMessage');

let gameState = 'waiting';
let currentMultiplier = 1.00;

function showNotification(text, color = '#f8fafc') {
    gameMessage.innerText = text;
    gameMessage.style.color = color;
}

function drawCanvas(multiplier, state) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = state === 'crashed' ? '#ef4444' : '#10b981';
    ctx.font = 'bold 32px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(multiplier.toFixed(2) + 'x', canvas.width / 2, canvas.height / 2 + 10);
}

async function pollGameState() {
    try {
        let response = await fetch('api/current_round.php');
        let data = await response.json();
        
        gameState = data.status;
        currentMultiplier = parseFloat(data.multiplier);
        
        if (data.balance !== undefined) {
            balanceDisplay.innerText = parseFloat(data.balance).toFixed(2);
        }
        
        drawCanvas(currentMultiplier, gameState);

        if (gameState === 'crashed') {
            multiplierDisplay.innerText = "Crashed at " + currentMultiplier.toFixed(2) + "x";
            if (actionBtn.dataset.status === "cashed") {
                actionBtn.innerText = "Place Bet";
                actionBtn.dataset.status = "idle";
            }
        } else if (gameState === 'running') {
            multiplierDisplay.innerText = currentMultiplier.toFixed(2) + "x";
        } else {
            multiplierDisplay.innerText = "Next round starting soon...";
        }
    } catch (e) {
        console.error("Polling error", e);
    }
}

actionBtn.addEventListener('click', async () => {
    let btnStatus = actionBtn.dataset.status || "idle";

    if (btnStatus === "idle") {
        let amount = betInput.value;
        let res = await fetch('api/place_bet.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ amount: amount })
        });
        let data = await res.json();
        
        if (data.success) {
            actionBtn.innerText = "Cash Out";
            actionBtn.dataset.status = "cashed";
            showNotification(data.message, '#10b981');
        } else {
            showNotification(data.message, '#ef4444');
        }
    } else if (btnStatus === "cashed") {
        let res = await fetch('api/cashout.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        });
        let data = await res.json();
        
        if (data.success) {
            showNotification(`Cashed out successfully! Payout: $${data.payout.toFixed(2)}`, '#10b981');
            actionBtn.innerText = "Place Bet";
            actionBtn.dataset.status = "idle";
        } else {
            showNotification(data.message, '#ef4444');
        }
    }
});

setInterval(pollGameState, 500);
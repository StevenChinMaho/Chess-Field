const boardElement = document.getElementById('board');  // 負責棋盤棋子
const stateElement = document.getElementById('status');  // 負責現在輪到誰
const logsElement = document.getElementById('logs');  // 負責棋譜紀錄

// 讀取 PHP 傳來的設定
const roomCode = GAME_CONFIG.roomCode;
const mySide = GAME_CONFIG.mySide;

// 初始化棋盤
let chessboardArr = stringToBoard(GAME_CONFIG.initialBoard);

let isPlaying = false;
let turn = GAME_CONFIG.initialTurn;  // 輪到誰
let selected = null;  // 被選取的棋子
let hints = [];  // 棋子可以合法移動到哪裡
let hasMoved = {};  // 棋子是否移動過
let enPassantTarget = null;  // 過路兵的攻擊點


const symbols = {
    'k': '♚\uFE0E', 'q': '♛\uFE0E', 'r': '♜\uFE0E', 'b': '♝\uFE0E', 'n': '♞\uFE0E', 'p': '♟\uFE0E',
    'K': '♚\uFE0E', 'Q': '♛\uFE0E', 'R': '♜\uFE0E', 'B': '♝\uFE0E', 'N': '♞\uFE0E', 'P': '♟\uFE0E'
}

// 將 64字元字串轉為 8x8 陣列的 Helper
function stringToBoard(str) {
    let arr = [];
    for(let r=0; r<8; r++) {
        let row = [];
        for(let c=0; c<8; c++) {
            let char = str[r*8 + c];
            row.push(char === '.' ? null : char);
        }
        arr.push(row);
    }
    return arr;
}

// 將 8x8 陣列轉為 64字元字串 (給後端用)
function boardToString(arr) {
    let str = "";
    for(let r=0; r<8; r++) {
        for(let c=0; c<8; c++) {
            str += (arr[r][c] === null ? '.' : arr[r][c]);
        }
    }
    return str;
}

function pieceDetail(pieceType) {
    if (!pieceType)
        return null;
    const isWhite = pieceType === pieceType.toUpperCase();
    return {
        color: isWhite ? 'w' : 'b',
        type: pieceType.toLowerCase()
    };
}

function getMoves(r, c, p) { 
    const detail = pieceDetail(p);
    const color = detail.color;
    const type = detail.type;

    let m = [];  // 儲存棋子可以走的位置

    const inBoard = (r,c) => r>=0 && r<8 && c>=0 && c<8;  // 棋子有沒有在棋盤的範圍裡

    const add = (tr,tc) => {
        if(!inBoard(tr,tc)) 
            return false;
        const target = chessboardArr[tr][tc];
        if(!target) { 
            m.push({r:tr, c:tc}); return true; 
        }
        const targetDetail = pieceDetail(target);
        if(targetDetail.color !== color)  // 判斷target顏色
            m.push({r:tr, c:tc});
        return false;
    };

    if(type === 'p') {  // 兵的走法
        const d = (color==='w') ? -1 : 1;  // 白兵往上走-1，黑兵往下走+1
        const start = (color==='w') ? 6 : 1;  // 兵的起始位置

        if(inBoard(r+d,c) && !chessboardArr[r+d][c]) {  // 往前走一格
            m.push({r:r+d, c:c});
            if(r===start && !chessboardArr[r+d*2][c])  // 往前走兩格
                m.push({r:r+d*2, c:c});
        }
        [[r+d,c-1], [r+d,c+1]].forEach(([tr,tc]) => {  // 斜吃: 有棋子且要是對手的棋子
            if(inBoard(tr,tc)) { 
                const t = chessboardArr[tr][tc]; 
                if(t) {
                    const tDetail = pieceDetail(t);
                    if(tDetail.color !== color) 
                        m.push({r:tr, c:tc}); 
                }
                else if(enPassantTarget && tr === enPassantTarget.r && tc === enPassantTarget.c) {  // 兵要走到的位置是空的，但那是過路兵的攻擊點
                    m.push({r:tr, c:tc, isEnPassant: true});  // 把過路兵加入棋子可以走的位置
                }
            }
        });
    }
    else if(type === 'n') {  // 騎士的走法
        [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]].forEach(([dr,dc]) => add(r+dr, c+dc)); 
    }
    else if(type === 'k') {  // 國王的走法
        [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]].forEach(([dr,dc]) => add(r+dr, c+dc));

        if(!hasMoved[`${r},${c}`]) {  // 國王沒動過
            // 短易位
            if(!chessboardArr[r][c+1] && !chessboardArr[r][c+2]) {  // 國王右側兩格是不是空的
                if (chessboardArr[r][c+3] && !hasMoved[`${r},${c+3}`]) {  // 右邊的城堡有沒有動過
                     m.push({r:r, c:c+2, isCastling: 'short'});  // 把短易位加入棋子可以走的位置
                }
            }

            // 長易位
            if (!chessboardArr[r][c-1] && !chessboardArr[r][c-2] && !chessboardArr[r][c-3]) {  // 國王左側三格是不是空的
                if (chessboardArr[r][c-4] && !hasMoved[`${r},${c-4}`]) {  // 左邊的城堡有沒有動過
                    m.push({r:r, c:c-2, isCastling: 'long'});  // 把長易位加入棋子可以走的位置
                }
            }
        }
    }
    else {
        const directions = [];

        if(type!=='b')  // 主教的走法
            directions.push([-1,0],[1,0],[0,-1],[0,1]);
        if(type!=='r')  // 城堡的走法
            directions.push([-1,-1],[-1,1],[1,-1],[1,1]);
        directions.forEach(([dr,dc]) => {  // 皇后的走法
            for(let i=1; i<8; i++) { 
                if(!add(r+dr*i, c+dc*i)) break; 
            } 
        });
    }

    return m;
}

function render() {
    boardElement.innerHTML = '';  // 清空棋盤
    
    // 如果我是黑方，視角要翻轉 (從 row 7 畫到 0，col 7 畫到 0)
    // 這裡我們用 CSS flex-direction 或者 JS 迴圈倒著跑都可以
    // 簡單解法：根據 mySide 決定是否倒置棋盤 HTML
    
    const isFlipped = (mySide === 'b');
    
    // 這裡維持原本邏輯生成 DOM，但如果是黑方，我們透過 CSS class 讓它旋轉
    if (isFlipped) {
        boardElement.style.transform = "rotate(180deg)";
    } else {
        boardElement.style.transform = "none";
    }

    for(let r=0; r<8; r++) {
        for(let c=0; c<8; c++) {
            const sq = document.createElement('div');
            sq.className = `sq ${(r+c)%2===0 ? 'light' : 'dark'}`;  // 格子的顏色(深、淺)
            
            if(selected && selected.r===r && selected.c===c)  // 如果格子被點擊會變色
                sq.classList.add('selected');

            // 提示點
            if(hints.find(h => h.r===r && h.c===c)) {  // 合法移動的提示
                const dot = document.createElement('div');
                dot.className = 'dot';
                sq.appendChild(dot);  // 把點放到格子裡
            }

            const p = chessboardArr[r][c];
            if(p) {
                const div = document.createElement('div');
                const detail = pieceDetail(p);
                
                div.innerText = symbols[p];  // 填入棋子Unicode
                div.className = `piece ${detail.color}`;  // 棋子顏色
                
                // ★ 關鍵：因為外層 board 轉了 180度，裡面的棋子也要轉回來，不然會倒立
                if (isFlipped) {
                    div.style.transform = "rotate(180deg)";
                }
                
                sq.appendChild(div);  // 把棋子放到格子裡
            }

            sq.onclick = () => click(r, c);  // 只要玩家點擊格子，就會觸發click
            boardElement.appendChild(sq);  // 把格子放到棋盤裡
        }
    }
    
    // 狀態顯示優化
    let statusText = (turn === 'w' ? "白方回合" : "黑方回合");
    if (turn === mySide) statusText += " (輪到你了)";
    stateElement.innerText = statusText;
}

function click(r, c) {
    // 觀戰者或非自己回合不能操作
    if (mySide === 'spectator') return;
    // 非playing狀態不可操作
    if (!isPlaying) return;
    // 如果現在不是我的回合，且我不是在選自己的棋子(防止亂點)，則禁止
    // 但為了讓使用者可以點選查看自己的棋子，我們只在「嘗試移動」時攔截
    
    const p = chessboardArr[r][c];
    let pDetail = p ? pieceDetail(p) : null;

    // A. 選擇棋子
    if(p && pDetail.color === turn) {  // 點到自己的棋子
        // 只能選自己顏色的棋子 (且要是自己的回合)
        if (turn !== mySide) return; 

        selected = {r,c};
        hints = getMoves(r, c, p);  // 計算棋子能走到哪
        render();
        return; 
    }

    if(selected) {  // 移動棋子
        const canMove = hints.find(h => h.r===r && h.c===c);  // 檢查點到的格子是不是棋子可以合法移動到的

        if(canMove) {
            const fromR = selected.r;
            const fromC = selected.c;
            const pieceOriginal = chessboardArr[selected.r][selected.c];  // 拿起原本位置的棋子
            const detailOriginal = pieceDetail(pieceOriginal);

            let moveString = "";
            const isCapture = chessboardArr[r][c] !== null || canMove.isEnPassant;
            if (canMove.isCastling) {
                moveString = (canMove.isCastling === 'short') ? "O-O" : "O-O-O";
            } 
            else {
                const pieceChar = detailOriginal.type.toUpperCase(); // P, N, B...
                const coords = String.fromCharCode(97 + c) + (8 - r); // e.g., e4

                if (detailOriginal.type === 'p') {
                    if (isCapture) {
                        moveString = String.fromCharCode(97 + fromC) + "x" + coords;
                    } else {
                        moveString = coords;
                    }
                } else {
                    moveString = pieceChar + (isCapture ? "x" : "") + coords;
                }

                if (detailOriginal.type === 'p' && (r === 0 || r === 7)) {
                    moveString += "=Q";
                }
            }

            chessboardArr[r][c] = pieceOriginal;  // 放到新的位置
            chessboardArr[selected.r][selected.c] = null;  // 清空舊的位置

            hasMoved[`${fromR},${fromC}`] = true;  // 表示棋子已經移動過
            hasMoved[`${r},${c}`] = true;  // 新的位置也算移動過

            if (canMove.isCastling) {
                if (canMove.isCastling === 'short') {  // 短易位
                    const rook = chessboardArr[r][fromC+3];
                    chessboardArr[r][fromC+1] = rook;
                    chessboardArr[r][fromC+3] = null;
                    hasMoved[`${r},${fromC+3}`] = true;  // 表示城堡動過了
                } else {  // 長易位
                    const rook = chessboardArr[r][fromC-4];
                    chessboardArr[r][fromC-1] = rook;
                    chessboardArr[r][fromC-4] = null;
                    hasMoved[`${r},${fromC-4}`] = true;
                }
            }

            if (canMove.isEnPassant) {  // 移除被吃得兵
                chessboardArr[fromR][c] = null; 
            }

            if (detailOriginal.type === 'p' && Math.abs(r - fromR) === 2) {
                // 過路兵的點在起點和終點的中間
                enPassantTarget = {
                    r: (r + fromR) / 2,
                    c: c
                };
            } else {
                // 如果不是走兩格，或者不是兵，重置過路兵的點 (過路兵機會只有一回合)
                enPassantTarget = null;
            }

            if(detailOriginal.type === 'p' && (r===0 || r===7)) {  // 兵的升變(目前只有后)
                chessboardArr[r][c] = (turn === 'w') ? 'Q' : 'q'; 
            }
            
            // 棋譜紀錄
            const logEntry = document.createElement('div');
            // 加上顏色提示
            logEntry.style.color = (turn === 'w') ? '#fff' : '#aaa';
            logEntry.innerText = moveString; // 例如 "Nf3" 或 "exd5"
            
            logsElement.appendChild(logEntry);
            logsElement.scrollTop = logsElement.scrollHeight;

            // 1. 產生棋盤字串
            const newBoardStr = boardToString(chessboardArr);
            
            // 2. 發送給後端
            sendMoveToServer(newBoardStr, moveString); // 記得把你原本算好的 moveString 傳進去

            // 3. 本地切換回合 (等待伺服器確認)
            turn = (turn==='w') ? 'b' : 'w';  // 交換回合
            selected = null;
            hints = [];
            render();
        } 
        else { 
            selected = null;  // 清空棋子被選取的狀態
            hints = [];
            render();
        }
    }
}

// === AJAX 發送移動 ===
async function sendMoveToServer(boardStr, moveText) {
    const formData = new FormData();
    formData.append('room_code', roomCode);
    formData.append('board', boardStr);
    formData.append('move_text', moveText);

    try {
        const res = await fetch('api/make-move.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.status !== 'success') {
            alert("移動失敗: " + data.message);
            location.reload(); // 同步失敗就重整
        }
    } catch (e) {
        console.error(e);
    }
}

// === SSE 監聽對手移動 ===
function initSSE() {
    const evtSource = new EventSource(`api/game-sse.php?room_code=${roomCode}`);
    
    evtSource.addEventListener('update', function(e) {
        const data = JSON.parse(e.data);
        console.log("收到更新:", data);
        
        // 更新本地資料
        chessboardArr = stringToBoard(data.board);
        turn = data.turn;
        isPlaying = data.status === "playing";
        
        // 重新繪製
        selected = null;
        hints = [];
        render();
    });
    
    evtSource.onerror = function() {
        console.log("SSE 連線中斷，嘗試重連...");
    };
}

document.getElementById('btn-reset').onclick = () => location.reload();  // Restart按鈕
// 啟動
initSSE();
render();  // game.php執行後，先把棋盤棋子畫出來
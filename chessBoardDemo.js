let chessboardArr = [['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'],
                    ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p'],
                    ['.', '.', '.', '.', '.', '.', '.', '.'],
                    ['.', '.', '.', '.', '.', '.', '.', '.'],
                    ['.', '.', '.', '.', '.', '.', '.', '.'],
                    ['.', '.', '.', '.', '.', '.', '.', '.'],
                    ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P'],
                    ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R']];

let chessboardStr = '';

chessboardArr.forEach((row) => {
    row.forEach( (piece) => {
        chessboardStr += piece;
    });
});

console.log(chessboardStr);

const a = document.getElementById("test");
a.innerHTML = chessboardStr;
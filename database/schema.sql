SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `chess`
--

-- --------------------------------------------------------

--
-- 資料表結構 `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `chessboard` char(64) DEFAULT 'rnbqkbnrpppppppp................................PPPPPPPPRNBQKBNR' COMMENT '棋盤狀態',
  `status` enum('deciding','waiting','playing','finished') DEFAULT 'deciding' COMMENT '遊戲狀態',
  `turn` char(1) NOT NULL DEFAULT 'w' COMMENT '黑/白回合',
  `p1_side` char(1) DEFAULT NULL COMMENT '玩家一的陣營',
  `p1_time` int(11) NOT NULL DEFAULT 5400 COMMENT '玩家一剩餘時間',
  `p2_time` int(11) NOT NULL DEFAULT 5400 COMMENT '玩家二剩餘時間',
  `time_increment` int(11) NOT NULL DEFAULT 30 COMMENT '每步加秒',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '上次落子時間',
  `castling_rights` char(4) NOT NULL DEFAULT 'KQkq' COMMENT '王車易位規則',
  `en_passant_target` varchar(2) DEFAULT NULL COMMENT '吃過路兵規則',
  `outcome` varchar(20) DEFAULT NULL COMMENT '比賽結果: w, b, draw, aborted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='棋局資料表';

-- --------------------------------------------------------

--
-- 資料表結構 `moves`
--

CREATE TABLE `moves` (
  `move_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL COMMENT '對應棋局',
  `chessboard` char(64) NOT NULL COMMENT '棋盤狀態',
  `move_text` varchar(20) NOT NULL COMMENT '棋譜',
  `move_time` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '落子時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='棋譜資料表';

-- --------------------------------------------------------

--
-- 資料表結構 `players`
--

CREATE TABLE `players` (
  `player_id` int(11) NOT NULL,
  `player_identity` varchar(100) NOT NULL COMMENT '玩家identity',
  `player_name` varchar(20) DEFAULT NULL COMMENT '玩家名稱',
  `last_room_code` varchar(20) DEFAULT NULL COMMENT '最後輸入房號'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='玩家資料表';

-- --------------------------------------------------------

--
-- 資料表結構 `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_code` varchar(20) NOT NULL COMMENT '房號',
  `p1_id` int(11) DEFAULT NULL COMMENT '玩家一',
  `p2_id` int(11) DEFAULT NULL COMMENT '玩家二',
  `game_id` int(11) NOT NULL COMMENT '棋局',
  `last_conn` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '上次房內玩家上線時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='房間資料表';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`);

--
-- 資料表索引 `moves`
--
ALTER TABLE `moves`
  ADD PRIMARY KEY (`move_id`),
  ADD KEY `fk_game_move_id` (`game_id`);

--
-- 資料表索引 `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`),
  ADD UNIQUE KEY `uk_player_identity` (`player_identity`) COMMENT '確保identity不重複';

--
-- 資料表索引 `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `uk_room_code` (`room_code`) COMMENT '查詢房號用',
  ADD KEY `fk_room_p1` (`p1_id`),
  ADD KEY `fk_room_p2` (`p2_id`),
  ADD KEY `fk_game_id` (`game_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `moves`
--
ALTER TABLE `moves`
  MODIFY `move_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `moves`
--
ALTER TABLE `moves`
  ADD CONSTRAINT `fk_game_move_id` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_game_id` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_p1` FOREIGN KEY (`p1_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_room_p2` FOREIGN KEY (`p2_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

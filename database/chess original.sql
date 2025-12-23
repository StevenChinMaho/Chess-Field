CREATE table `players` (
    `player_id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    `player_identity` VARCHAR(100) COMMENT '玩家identity',
    `player_name` VARCHAR(20) COMMENT '玩家名稱', 
    `last_room_code` VARCHAR(20) COMMENT '最後輸入房號'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='玩家資料表';

CREATE table `games` (
    `game_id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    `chessboard` CHAR(64) DEFAULT 'rnbqkbnrpppppppp................................PPPPPPPPRNBQKBNR' COMMENT '棋盤狀態',
    `status` ENUM('deciding', 'waiting', 'playing', 'finished') DEFAULT 'deciding' COMMENT '遊戲狀態',
    `turn` char(1) DEFAULT 'w' NOT NULL COMMENT '黑/白回合', 
    `p1_side` CHAR(1) COMMENT '玩家一的陣營',
    `p1_time` INT DEFAULT 5400 NOT NULL COMMENT '玩家一剩餘時間',
    `p2_time` INT DEFAULT 5400 NOT NULL COMMENT '玩家二剩餘時間',
    `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '上次落子時間',
    `castling_rights` CHAR(6) DEFAULT 'rkrRKR' NOT NULL COMMENT '王車易位規則',
    `en_passant_target` VARCHAR(2) DEFAULT NULL COMMENT '吃過路兵規則'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='棋局資料表';

CREATE table `rooms` (
    `room_id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    `room_code` VARCHAR(20) NOT NULL COMMENT '房號',
    `p1_id` INT DEFAULT NULL COMMENT '玩家一',
    `p2_id` INT DEFAULT NULL COMMENT '玩家二',
    `game_id` INT NOT NULL COMMENT '棋局',
    `last_conn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '上次房內玩家上線時間',
    CONSTRAINT `fk_room_p1`
        FOREIGN KEY (`p1_id`)
        REFERENCES `players` (`player_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_room_p2`
        FOREIGN KEY (`p2_id`)
        REFERENCES `players` (`player_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_game_id`
        FOREIGN KEY (`game_id`)
        REFERENCES `games` (`game_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='房間資料表';

CREATE table `moves` (
    `move_id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    `game_id` INT NOT NULL COMMENT '對應棋局',
    `chessboard` CHAR(64) NOT NULL COMMENT '棋盤狀態',
    `move_text` VARCHAR(20) NOT NULL COMMENT '棋譜',
    `move_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '落子時間',
    CONSTRAINT `fk_game_move_id`
        FOREIGN KEY (`game_id`)
        REFERENCES `games` (`game_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='棋譜資料表';

ALTER TABLE `players`
    ADD UNIQUE KEY `uk_player_identity` (`player_identity`) COMMENT '確保identity不重複';

ALTER TABLE `rooms`
    ADD UNIQUE KEY `uk_room_code` (`room_code`) COMMENT '查詢房號用';
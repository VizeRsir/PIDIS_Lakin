-- ============================================================
--  API «Заметки»
--  Таблицы: users, notes
-- ============================================================

SET NAMES utf8mb4;

-- Пользователи (для связки login)
CREATE TABLE IF NOT EXISTS `users` (
    `id`    INT AUTO_INCREMENT PRIMARY KEY,
    `login` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заметки
CREATE TABLE IF NOT EXISTS `notes` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `title`      VARCHAR(200) NOT NULL,
    `content`    TEXT         NOT NULL,
    `id_user`    INT          NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_notes_user`
        FOREIGN KEY (`id_user`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_notes_user` (`id_user`),
    INDEX `idx_notes_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Тестовые данные (5 пользователей и 5 заметок)
-- ============================================================

INSERT INTO `users` (`id`, `login`) VALUES
    (1, 'user25'),
    (2, 'admin'),
    (3, 'ivanov'),
    (4, 'petrova'),
    (5, 'sidorov')
ON DUPLICATE KEY UPDATE `login` = VALUES(`login`);

INSERT INTO `notes` (`id`, `title`, `content`, `id_user`, `created_at`) VALUES
    (1, 'Конференция ИТ',
        'Обсуждение новых технологий и планов на квартал.',
        1, '2027-03-15 10:00:00'),
    (2, 'Заявка на ремонт',
        'Требуется замена труб в подвале дома №12.',
        3, '2027-03-14 09:30:00'),
    (3, 'Собрание собственников',
        'Повестка: капитальный ремонт и тарифы.',
        4, '2027-03-12 18:00:00'),
    (4, 'Проверка счётчиков',
        'Передать показания до 25 числа.',
        5, '2027-03-10 12:15:00'),
    (5, 'Напоминание',
        'Проверить статус заявки №2027-0042.',
        2, '2027-03-08 08:45:00')
ON DUPLICATE KEY UPDATE
    `title`   = VALUES(`title`),
    `content` = VALUES(`content`);
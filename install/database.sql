


-- 基础分类表
-- 剧集分类表
CREATE TABLE IF NOT EXISTS drama_categories (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 文章分类表
CREATE TABLE IF NOT EXISTS article_categories (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 核心内容表
-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role VARCHAR(20) DEFAULT 'user',
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 剧集表
CREATE TABLE IF NOT EXISTS dramas (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    link TEXT,
    description TEXT,
    category_id INTEGER,
    image_path TEXT,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES drama_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 文章表
CREATE TABLE IF NOT EXISTS articles (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INTEGER,
    image_path TEXT,
    content TEXT NOT NULL,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 交互表
-- 评论表
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    article_id INTEGER,
    drama_id INTEGER,
    user_id INTEGER NOT NULL,
    is_deleted TINYINT DEFAULT 0,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (drama_id) REFERENCES dramas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    -- 确保评论至少关联一个内容
    CONSTRAINT comment_content_check CHECK (
        (article_id IS NOT NULL AND drama_id IS NULL) OR
        (article_id IS NULL AND drama_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 点赞表
CREATE TABLE IF NOT EXISTS likes (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    content_type VARCHAR(20) NOT NULL,
    content_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    -- 确保内容类型合法
    CONSTRAINT content_type_check CHECK (content_type IN ('article', 'drama')),
    -- 避免同一用户重复点赞同一内容
    UNIQUE KEY unique_like (content_type, content_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 扩展功能表
-- 标签表
CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 文章-标签关联表
CREATE TABLE IF NOT EXISTS article_tags (
    article_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 短剧-标签关联表
CREATE TABLE IF NOT EXISTS drama_tags (
    drama_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY (drama_id, tag_id),
    FOREIGN KEY (drama_id) REFERENCES dramas(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 统计与配置表
-- 统计信息表
CREATE TABLE IF NOT EXISTS statistics (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    content_type VARCHAR(20) NOT NULL,
    content_id INTEGER NOT NULL,
    click_count INTEGER DEFAULT 0,
    comment_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- 确保内容类型合法
    CONSTRAINT stats_content_type_check CHECK (content_type IN ('article', 'drama')),
    UNIQUE KEY unique_statistic (content_type, content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 搜索关键词表
CREATE TABLE IF NOT EXISTS search_keywords (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    keyword VARCHAR(100) NOT NULL UNIQUE,
    count INTEGER DEFAULT 1,
    last_searched DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 网站设置表
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL,
    site_url VARCHAR(255) NOT NULL,
    site_keywords TEXT,
    site_description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 初始化默认设置
INSERT IGNORE INTO settings (site_name, site_url) VALUES ('短剧网站', 'http://localhost');

-- 创建触发器：自动更新评论数统计
DELIMITER //
CREATE TRIGGER update_comment_count_after_insert
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    IF NEW.article_id IS NOT NULL THEN
        INSERT INTO statistics (content_type, content_id, comment_count)
        VALUES ('article', NEW.article_id, 1)
        ON DUPLICATE KEY UPDATE comment_count = comment_count + 1;
    ELSEIF NEW.drama_id IS NOT NULL THEN
        INSERT INTO statistics (content_type, content_id, comment_count)
        VALUES ('drama', NEW.drama_id, 1)
        ON DUPLICATE KEY UPDATE comment_count = comment_count + 1;
    END IF;
END //

CREATE TRIGGER update_comment_count_after_delete
AFTER DELETE ON comments
FOR EACH ROW
BEGIN
    IF OLD.article_id IS NOT NULL THEN
        UPDATE statistics 
        SET comment_count = comment_count - 1 
        WHERE content_type = 'article' AND content_id = OLD.article_id;
    ELSEIF OLD.drama_id IS NOT NULL THEN
        UPDATE statistics 
        SET comment_count = comment_count - 1 
        WHERE content_type = 'drama' AND content_id = OLD.drama_id;
    END IF;
END //
DELIMITER ;

-- 创建触发器：自动更新点赞数统计
DELIMITER //
CREATE TRIGGER update_like_count_after_insert
AFTER INSERT ON likes
FOR EACH ROW
BEGIN
    INSERT INTO statistics (content_type, content_id, like_count)
    VALUES (NEW.content_type, NEW.content_id, 1)
    ON DUPLICATE KEY UPDATE like_count = like_count + 1;
END //

CREATE TRIGGER update_like_count_after_delete
AFTER DELETE ON likes
FOR EACH ROW
BEGIN
    UPDATE statistics 
    SET like_count = like_count - 1 
    WHERE content_type = OLD.content_type AND content_id = OLD.content_id;
END //
DELIMITER ;

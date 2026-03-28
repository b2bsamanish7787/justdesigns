-- Just Designs - Database Schema
-- Run this file to set up the database

CREATE DATABASE IF NOT EXISTS justdesigns CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE justdesigns;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    is_subscriber TINYINT(1) DEFAULT 1,
    must_change_password TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Image categories/entries table
CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    dimensions VARCHAR(50),
    image_type ENUM('free','premium') DEFAULT 'free',
    primary_image VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Image gallery files table (multiple images per entry)
CREATE TABLE IF NOT EXISTS image_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT NOT NULL,
    filename VARCHAR(500) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, image_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Default admin account (password: admin123)
-- ⚠️  IMPORTANT: Change this password immediately after setup!
INSERT IGNORE INTO admins (username, password, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@justdesigns.com');

-- Sample free images
INSERT IGNORE INTO images (image_code, name, description, dimensions, image_type, primary_image) VALUES
('FREE001','Abstract Blue','A vibrant abstract blue composition.','1920x1080','free','https://picsum.photos/seed/free1/800/600'),
('FREE002','Nature Green','Lush green forest landscape.','1920x1080','free','https://picsum.photos/seed/free2/800/600'),
('FREE003','Urban Architecture','Modern city architecture.','1920x1080','free','https://picsum.photos/seed/free3/800/600'),
('FREE004','Ocean Waves','Calming ocean waves at sunset.','1920x1080','free','https://picsum.photos/seed/free4/800/600'),
('FREE005','Mountain Peak','Snow-capped mountain peak.','1920x1080','free','https://picsum.photos/seed/free5/800/600'),
('FREE006','Desert Dunes','Golden desert sand dunes.','1920x1080','free','https://picsum.photos/seed/free6/800/600'),
('FREE007','Floral Garden','Colorful spring flower garden.','1920x1080','free','https://picsum.photos/seed/free7/800/600'),
('FREE008','Night Sky','Starry night sky milky way.','1920x1080','free','https://picsum.photos/seed/free8/800/600'),
('FREE009','Waterfall','Tropical waterfall in rainforest.','1920x1080','free','https://picsum.photos/seed/free9/800/600'),
('FREE010','Cityscape','Downtown cityscape at night.','1920x1080','free','https://picsum.photos/seed/free10/800/600'),
('FREE011','Forest Path','Misty forest trail in autumn.','1920x1080','free','https://picsum.photos/seed/free11/800/600'),
('FREE012','Beach Sunset','Tropical beach at golden hour.','1920x1080','free','https://picsum.photos/seed/free12/800/600'),
('FREE013','Canyon Walls','Colorful canyon rock formations.','1920x1080','free','https://picsum.photos/seed/free13/800/600'),
('FREE014','Lake Reflection','Serene lake with mountain reflection.','1920x1080','free','https://picsum.photos/seed/free14/800/600'),
('FREE015','Autumn Leaves','Vibrant autumn foliage.','1920x1080','free','https://picsum.photos/seed/free15/800/600'),
('FREE016','Snowy Landscape','Winter snow covered landscape.','1920x1080','free','https://picsum.photos/seed/free16/800/600'),
('FREE017','Tropical Birds','Colorful parrots in rainforest.','1920x1080','free','https://picsum.photos/seed/free17/800/600'),
('FREE018','Aerial View','Aerial drone photo of farmland.','1920x1080','free','https://picsum.photos/seed/free18/800/600'),
('FREE019','Stone Bridge','Ancient stone bridge over river.','1920x1080','free','https://picsum.photos/seed/free19/800/600'),
('FREE020','Lighthouse','Classic lighthouse on rocky coast.','1920x1080','free','https://picsum.photos/seed/free20/800/600'),
('FREE021','Meadow Flowers','Wildflower meadow in spring.','1920x1080','free','https://picsum.photos/seed/free21/800/600'),
('FREE022','Coastal Cliffs','Dramatic sea cliffs at dusk.','1920x1080','free','https://picsum.photos/seed/free22/800/600'),
('FREE023','River Delta','Winding river delta from above.','1920x1080','free','https://picsum.photos/seed/free23/800/600'),
('FREE024','Glacier Ice','Arctic glacier blue ice.','1920x1080','free','https://picsum.photos/seed/free24/800/600'),
('FREE025','Volcano','Lava flowing from active volcano.','1920x1080','free','https://picsum.photos/seed/free25/800/600'),
('FREE026','Bamboo Forest','Tall bamboo grove in Japan.','1920x1080','free','https://picsum.photos/seed/free26/800/600'),
('FREE027','Cherry Blossoms','Pink cherry blossom trees.','1920x1080','free','https://picsum.photos/seed/free27/800/600'),
('FREE028','Cobblestone Street','European cobblestone alley.','1920x1080','free','https://picsum.photos/seed/free28/800/600'),
('FREE029','Sunrise Fog','Foggy valley at sunrise.','1920x1080','free','https://picsum.photos/seed/free29/800/600'),
('FREE030','Rocky Shore','Rocks and tide pools on shore.','1920x1080','free','https://picsum.photos/seed/free30/800/600');

-- Sample premium images
INSERT IGNORE INTO images (image_code, name, description, dimensions, image_type, primary_image) VALUES
('PREM001','Premium Abstract','Exclusive abstract digital art.','4K','premium','https://picsum.photos/seed/prem1/800/600'),
('PREM002','Premium Architecture','Award-winning architecture photo.','4K','premium','https://picsum.photos/seed/prem2/800/600'),
('PREM003','Premium Landscape','Professional landscape photo.','4K','premium','https://picsum.photos/seed/prem3/800/600'),
('PREM004','Premium Wildlife','Rare wildlife photography.','4K','premium','https://picsum.photos/seed/prem4/800/600'),
('PREM005','Premium Macro','Stunning macro photography.','4K','premium','https://picsum.photos/seed/prem5/800/600'),
('PREM006','Premium Astrophoto','Deep space astrophotography.','4K','premium','https://picsum.photos/seed/prem6/800/600'),
('PREM007','Premium Underwater','Professional underwater photo.','4K','premium','https://picsum.photos/seed/prem7/800/600'),
('PREM008','Premium Portrait','Fine art portrait photography.','4K','premium','https://picsum.photos/seed/prem8/800/600'),
('PREM009','Premium Cityscape','Long exposure city photography.','4K','premium','https://picsum.photos/seed/prem9/800/600'),
('PREM010','Premium Nature','Award-winning nature photography.','4K','premium','https://picsum.photos/seed/prem10/800/600'),
('PREM011','Premium Design 1','Exclusive graphic design artwork.','4K','premium','https://picsum.photos/seed/prem11/800/600'),
('PREM012','Premium Design 2','Exclusive digital illustration.','4K','premium','https://picsum.photos/seed/prem12/800/600'),
('PREM013','Premium Texture','High-res luxury gold texture.','4K','premium','https://picsum.photos/seed/prem13/800/600'),
('PREM014','Premium Vintage','Exclusive vintage-style design.','4K','premium','https://picsum.photos/seed/prem14/800/600'),
('PREM015','Premium Pattern','Luxury seamless pattern design.','4K','premium','https://picsum.photos/seed/prem15/800/600');

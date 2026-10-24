-- Kalaat Bona FC - demo/default data
-- Import this file after database/schema.sql.
-- No real user accounts, emails, phone numbers or passwords are included.

SET NAMES utf8mb4;

INSERT INTO `settings` (`s_key`, `s_value`) VALUES
('site_name', 'Kallat Bona Football Club'),
('site_logo', 'logo_kala.webp'),
('primary_color', '#0F4B92'),
('secondary_color', '#C5A059'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_encryption', 'tls'),
('mail_from', ''),
('mail_from_name', 'Kallat Bona FC');

INSERT INTO `programme` (`id_programme`, `id_coach`, `nom_programme`, `description`, `icone`, `lieu`, `date_horaire`, `places`, `age`, `destinations`, `inscriptions_info`, `age_min`, `age_max`) VALUES
(1, NULL, 'Formation Technique', 'Développement des compétences techniques individuelles : contrôle, passe, dribble et finition.', '⚽', 'Stade Kalaat Bona, Annaba', 'Mercredi et samedi', '30 joueurs', '8-16 ans', 'Progression technique et tactique.', 'Inscriptions trimestrielles', 8, 16),
(2, NULL, 'Compétition', 'Participation aux matchs et tournois pour les joueurs souhaitant évoluer en compétition.', '🏆', 'Stade Kalaat Bona, Annaba', 'Selon le calendrier', '25 joueurs', '10-18 ans', 'Préparation mentale et physique à la compétition.', 'Sur sélection', 10, 18),
(3, NULL, 'Loisir', 'Football récréatif dans un cadre convivial et sécurisé.', '🎉', 'Annexe du stade, Annaba', 'Samedi après-midi', '40 joueurs', '6-14 ans', 'Plaisir du jeu et socialisation.', 'Ouvertes toute l''année', 6, 14),
(4, NULL, 'Elite', 'Programme avancé pour les joueurs à fort potentiel.', '⭐', 'Centre d''entraînement, Annaba', 'Lundi, mercredi et vendredi', '20 joueurs', '12-18 ans', 'Accompagnement vers le haut niveau.', 'Sur sélection', 12, 18),
(5, NULL, 'Baby-Foot', 'Initiation au football pour les tout-petits (4 à 6 ans).', '🧸', 'Stade Kalaat Bona, Annaba', 'Tous les samedis matin', '20 enfants par session', '4-6 ans', 'Découverte du jeu, motricité, plaisir et socialisation.', 'Ouvertes toute l''année', 4, 6),
(6, NULL, 'Entraînement de Groupe', 'Séances collectives pour jeunes de 6 à 18 ans, répartis par niveau et âge.', '👥', 'Stade principal et annexes', 'Lundi, mercredi, vendredi après-midi', '60 par créneau (groupes de 12)', '6-18 ans', 'Développement technique, tactique, esprit d''équipe, compétitions internes.', 'Trimestrielles', 6, 18),
(7, NULL, 'Entraînement Personnel', 'Coaching individuel pour tous les âges, adapté aux besoins spécifiques.', '🎯', 'Terrain VIP ou à domicile (sur demande)', 'Sur rendez-vous', 'Limité à 5 séances/jour', 'Ouvert à tous', 'Travail technique, remise en forme, préparation physique ou mentale.', 'Sur réservation', NULL, NULL),
(8, NULL, 'Formation VIP', 'Programme sur-mesure pour joueurs à fort potentiel ou besoins spécifiques.', '👑', 'Espace VIP, Annaba', 'Selon la durée choisie (1 semaine à 3 mois)', '10 maximum par session', 'Selon le projet du joueur', 'Perfectionnement, suivi personnalisé, bilan complet, orientation carrière.', 'Sur sélection', NULL, NULL),
(9, NULL, 'Stage Prochain', 'Stages intensifs pendant les vacances scolaires.', '📅', 'Annaba et destinations partenaires', 'Prochain stage : juillet', '30 par stage', '8-16 ans', 'Immersion, compétition, découverte d''autres clubs, activités ludiques.', 'Places limitées', 8, 16);

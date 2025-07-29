-- Script per aggiungere il campo tipo_straordinario alla tabella extra_hours
-- Eseguire questo script tramite phpMyAdmin o altro strumento di gestione database

USE straordinari;

-- Aggiunge il campo tipo_straordinario alla tabella extra_hours
ALTER TABLE extra_hours 
ADD COLUMN tipo_straordinario ENUM('feriale', 'festivo', 'notturno') NOT NULL DEFAULT 'feriale' 
AFTER description;

-- Aggiorna i record esistenti impostando il tipo come 'feriale' per default
UPDATE extra_hours SET tipo_straordinario = 'feriale' WHERE tipo_straordinario IS NULL;

-- Verifica la struttura aggiornata
DESCRIBE extra_hours; 
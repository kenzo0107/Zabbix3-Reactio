ALTER TABLE alerts ADD reactio_incident_id INT(11) NOT NULL DEFAULT 0 COMMENT 'Reactio Incident ID' AFTER alerttype;

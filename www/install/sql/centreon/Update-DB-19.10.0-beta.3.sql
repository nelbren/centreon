--
-- Add new field for Remote Server option
--
ALTER TABLE nagios_server ADD COLUMN `remote_server_centcore_ssh_proxy` enum('0','1') NOT NULL DEFAULT '1';

-- Update broker form
UPDATE `cb_field` SET `fieldname` = 'rrd_cached_option', `displayname` = 'Enable RRDCached', `description` = 'Enable rrdcached option for Centreon, please see Centreon documentation to configure it.', `fieldtype` = 'radio', `external` = NULL
WHERE `fieldname` = 'path' AND `displayname` = 'Unix socket';

UPDATE `cb_field` SET `fieldname` = 'rrd_cached', `displayname` = 'RRDCacheD listening socket/port', `description` = 'The absolute path to unix socket or TCP port for communicating with rrdcached daemon.', `fieldtype` = 'text', `external` = NULL
WHERE `fieldname` = 'port' AND `displayname` = 'TCP port';

INSERT INTO `cb_list` (`cb_list_id`, `cb_field_id`, `default_value`)
VALUES((SELECT coalesce(MAX(l.cb_list_id),0)+1 from cb_list l), (SELECT `cb_field_id` FROM `cb_field` WHERE `description` = 'Enable rrdcached option for Centreon, please see Centreon documentation to configure it.'), 'disable');

INSERT INTO `cb_list_values` (`cb_list_id`, `value_name`, `value_value`)
VALUES
((SELECT `cb_list_id` FROM `cb_list` WHERE `cb_field_id` =
  (SELECT `cb_field_id` FROM `cb_field` WHERE `description` = 'Enable rrdcached option for Centreon, please see Centreon documentation to configure it.')), 'Disable', 'disable'
),
((SELECT `cb_list_id` FROM `cb_list` WHERE `cb_field_id` =
  (SELECT `cb_field_id` FROM `cb_field` WHERE `description` = 'Enable rrdcached option for Centreon, please see Centreon documentation to configure it.')), 'UNIX Socket', 'unix'
),
((SELECT `cb_list_id` FROM `cb_list` WHERE `cb_field_id` =
  (SELECT `cb_field_id` FROM `cb_field` WHERE `description` = 'Enable rrdcached option for Centreon, please see Centreon documentation to configure it.')), 'TCP Port ', 'tcp'
);

UPDATE `cb_type_field_relation` SET `jshook_name` = 'rrdArguments', `jshook_arguments` = '{"target": "rrd_cached"}'
WHERE `cb_type_id` = (SELECT `cb_type_id` FROM `cb_type` WHERE `type_shortname` = 'rrd') AND `cb_field_id` = (SELECT `cb_field_id` FROM `cb_field` WHERE `description` = 'Enable rrdcached option for Centreon, please see Centreon documentation to configure it.');

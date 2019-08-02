<?php
/*
 * Copyright 2005 - 2019 Centreon (https://www.centreon.com/)
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */
 
namespace ConfigGenerateRemote;

use \PDO;

class Resource extends AbstractObject
{
    protected $table = 'cfg_resource';
    protected $generate_filename = 'resource.infile';
    protected $object_name = null;
    protected $stmt = null;
    protected $attributes_write = array(
        'resource_id',
        'resource_name',
        'resource_line',
        'resource_comment',
        'resource_activate'
    );

    public function generateFromPollerId($poller_id)
    {
        if (is_null($poller_id)) {
            return 0;
        }

        if (is_null($this->stmt)) {
            $query = "SELECT cfg_resource.resource_id, resource_name, resource_line, resource_comment, resource_activate FROM cfg_resource_instance_relations, cfg_resource " .
                "WHERE instance_id = :poller_id AND cfg_resource_instance_relations.resource_id = " .
                "cfg_resource.resource_id AND cfg_resource.resource_activate = '1'";
            $this->stmt = $this->backend_instance->db->prepare($query);
        }
        $this->stmt->bindParam(':poller_id', $poller_id, PDO::PARAM_INT);
        $this->stmt->execute();

        foreach ($this->stmt->fetchAll(PDO::FETCH_ASSOC) as $value) {
            cfgResourceInstanceRelation::getInstance($this->dependencyInjector)->addRelation($value['resource_id'], $poller_id);
            if ($this->checkGenerate($value['resource_id'])) {
                continue;
            }
            $this->generateObjectInFile($value, $value['resource_id']);
        }
    }
}

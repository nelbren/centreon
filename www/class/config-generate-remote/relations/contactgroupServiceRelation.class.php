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

class contactgroupServiceRelation extends AbstractObject
{
    protected $table = 'contactgroup_service_relation';
    protected $generate_filename = 'contactgroup_service_relation.infile';
    protected $attributes_write = [
        'service_service_id',
        'contactgroup_cg_id',
    ];

    public function addRelation($service_id, $cg_id)
    {
        $relation = [
            'service_service_id' => $service_id,
            'contactgroup_cg_id' => $cg_id,
        ];
        $this->generateObjectInFile($relation);
    }
}

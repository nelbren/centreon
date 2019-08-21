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

class macroHost extends AbstractObject
{
    protected $table = 'on_demand_macro_host';
    protected $generate_filename = 'on_demand_macro_host.infile';
    protected $attributes_write = [
        'host_host_id',
        'host_macro_name',
        'host_macro_value',
        'is_password',
        'description',
    ];

    public function add($object, $host_id)
    {
        if ($this->checkGenerate($host_id)) {
            return null;
        }

        $this->generateObjectInFile($object, $host_id);
    }
}

<?php
/*
 * Author: Paige A. Thompson (paigeadele@gmail.com)
 * Copyright (c) 2018, Netcrave Communications
 * All rights reserved.
 *
 *
 * Author: Trevor A. Thompson (trevorat@gmail.com)
 * Copyright (c) 2007, Progressive Solutions Inc.
 * All rights reserved.
 *
 * - Redistribution and use of this software in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above
 * copyright notice, this list of conditions and the
 * following disclaimer.
 *
 * - Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other
 * materials provided with the distribution.
 *
 * - Neither the name of Progressive Solutions Inc. nor the names of its
 * contributors may be used to endorse or promote products
 * derived from this software without specific prior
 * written permission of Progressive Solutions Inc.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace pscr\extensions\pscr_rest_api;

use pscr\lib\exceptions\invalid_argument_exception;
use pscr\lib\exceptions\not_implemented_exception;
use pscr\lib\http\response;
use pscr\lib\logging\logger;
use pscr\lib\model\i_content_renderer;

class pscr_rest_api implements i_content_renderer {
    private $request;

    function __construct() {
    }

    function set_request($request) {
        $this->request = $request;
    }

    function render() {
        $module_filename = $this->request->get_selected_route_entry_file_name();
        $module_classname = $this->request->get_selected_route_entry_class_name();

        logger::_()->info($this, "trying to instantiate ", $module_classname, $module_filename);
        require_once($module_filename);
        logger::_()->info($this, array("included ", $module_filename));
        //logger::_()->info($this, get_declared_classes());
        new $module_classname();
        if (class_exists($module_classname)) {
            if (is_a(new $module_classname(), 'pscr\extensions\pscr_rest_api\model\pscr_rest_api')) {
                $module = (new $module_classname());

                $module->set_request_instance($this->request);
                $module->set_response_instance($this->response);
                $module->handle_request();

                return($module);

            }
            else {
                throw new invalid_argument_exception("found class but class does not extend the pscr_rest_api class or implement the i_pscr_rest_api interface.");
            }
        }
        else {
            throw new invalid_argument_exception("class does not exist in module file");
        }
    }

    /**
     * @return response
     * @throws invalid_argument_exception
     */
    function render_to_response() {
        $this->response = new response($this->request);
        $module = $this->render();
        $content = json_encode($module->get_result());
        logger::_()->info($this, $content);
        $this->response->set_response_body($content);
        return $this->response;
    }
}

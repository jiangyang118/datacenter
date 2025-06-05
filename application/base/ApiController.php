<?php

namespace app\base;

use think\Controller;
class ApiController extends Controller {
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
    }
}

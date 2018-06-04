<?php

class CyAdmin extends \Phalcon\Mvc\Model {
    /* 验证状态 */

    const status_email = 1;			//邮箱验证
    const status_phone = 2;			//电话验证
    const status_idcard = 4;		//身份证验证

}
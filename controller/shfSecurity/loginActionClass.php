<?php

use mvc\interfaces\controllerActionInterface;
use mvc\controller\controllerClass;
use mvc\config\configClass as config;
use mvc\request\requestClass as request;
use mvc\routing\routingClass as routing;
use mvc\session\sessionClass as session;
use mvc\i18n\i18nClass as i18n;

/**
 * Description of loginActionClass
 *
 * @author Julian Lasso <ingeniero.julianlasso@gmail.com>
 */
class loginActionClass extends controllerClass implements controllerActionInterface {

  public function execute() {
    try {
      if (request::getInstance()->isMethod('POST')) {
        $usuario = request::getInstance()->getPost('inputUser');
        $password = request::getInstance()->getPost('inputPassword');

        if (($objUsuario = usuarioTableClass::verifyUser($usuario, $password)) !== false) {
          hook\security\securityHookClass::login($objUsuario);
          //self::login($objUsuario);
          if (request::getInstance()->hasPost('chkRememberMe') === true) {
            $chkRememberMe = request::getInstance()->getPost('chkRememberMe');
            $hash = md5($objUsuario[0]->id_usuario . $objUsuario[0]->usuario . date(config::getFormatTimestamp()));
            $data = array(
                recordarMeTableClass::USUARIO_ID => $objUsuario[0]->id_usuario,
                recordarMeTableClass::HASH_COOKIE => $hash,
                recordarMeTableClass::IP_ADDRESS => request::getInstance()->getServer('REMOTE_ADDR'),
                recordarMeTableClass::CREATED_AT => date(config::getFormatTimestamp())
            );
            recordarMeTableClass::insert($data);
            setcookie(config::getCookieNameRememberMe(), $hash, time() + config::getCookieTime(), config::getCookiePath());
          }
          hook\security\securityHookClass::redirectUrl();
          //self::redirectUrl();
        } else {
          session::getInstance()->setError('Usuario y contraseña incorrectos');
          routing::getInstance()->redirect(config::getDefaultModuleSecurity(), config::getDefaultActionSecurity());
        }
      } else {
        routing::getInstance()->redirect(config::getDefaultModule(), config::getDefaultAction());
      }
    } catch (PDOException $exc) {
      echo $exc->getMessage();
      echo '<br>';
      echo $exc->getTraceAsString();
    }
  }

  private static function redirectUrl() {
    if (session::getInstance()->hasAttribute('shfSecurityModuleGO') and session::getInstance()->hasAttribute('shfSecurityActionGO')) {
      $variables = null;
      if (session::getInstance()->hasAttribute('shfSecurityQueryString')) {
        $variables = array();
        parse_str(session::getInstance()->getAttribute('shfSecurityQueryString'), $variables);
      }
      routing::getInstance()->redirect(session::getInstance()->getAttribute('shfSecurityModuleGO'), session::getInstance()->getAttribute('shfSecurityActionGO'), $variables);
    } else {
      routing::getInstance()->redirect(config::getDefaultModule(), config::getDefaultAction());
    }
  }

  private static function login($objUsuario) {
    session::getInstance()->setUserAuthenticate(true);
    session::getInstance()->setUserName($objUsuario[0]->usuario);
    session::getInstance()->setUserId($objUsuario[0]->id_usuario);
    foreach ($objUsuario as $usuario) {
      session::getInstance()->setCredential($usuario->credencial);
    }
    usuarioTableClass::setRegisterLastLoginAt($objUsuario[0]->id_usuario);
    return true;
  }

}

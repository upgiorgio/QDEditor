<?php
/**
 * QDEditor CK5 - CKEditor 5（Classic）CDN 默认，支持可选本地化（v4.2-cdn）
 *
 * @package QDEditorCK5
 * @author aieii
 * @version 4.2.1
 * @link https://github.com/aieii/QDEditor-CK5
 * @license MIT
 * @description CDN 为默认加载方式，支持分栏可拖拽、同步滚动、自动上传、外链图片自动本地化（可选）、导出、状态栏、暗色主题、多语言（简/繁/英）。
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class QDEditorCK5_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('admin/footer.php')->end = array('QDEditorCK5_Plugin', 'inject');
        Helper::addAction('qdck5-upload', 'QDEditorCK5_Action');
        Helper::addAction('qdck5-fetch', 'QDEditorCK5_Fetch');
        return _t('QDEditor CK5 v4.2（CDN默认）已启用');
    }

    public static function deactivate(){
        Helper::removeAction('qdck5-upload');
        Helper::removeAction('qdck5-fetch');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $uiLang = new Typecho_Widget_Helper_Form_Element_Select('uiLang',
            array('zh-CN'=>'简体中文','zh-TW'=>'繁體中文','en'=>'English'),
            'zh-CN', _t('界面语言（UI Language）'));
        $form->addInput($uiLang);

        // 默认 CDN
        $cdn = new Typecho_Widget_Helper_Form_Element_Radio('useCDN', array('1'=>_t('CDN（默认）'),'0'=>_t('本地化（手动放置文件）')), '1', _t('加载方式'));
        $form->addInput($cdn);
        $cdnUrl = new Typecho_Widget_Helper_Form_Element_Text('cdnUrl', NULL, 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js', _t('CKEditor 5 CDN 地址'));
        $form->addInput($cdnUrl);

        $height = new Typecho_Widget_Helper_Form_Element_Text('height', NULL, '720', _t('编辑区高度(px)，约30行'));
        $form->addInput($height);

        $toolbar = new Typecho_Widget_Helper_Form_Element_Text(
            'toolbar', NULL,
            'heading,bold,italic,underline,strikethrough,link,blockQuote,codeBlock,bulletedList,numberedList,insertTable,undo,redo',
            _t('工具栏（逗号分隔；依据 CK5 Classic 可用项）')
        );
        $form->addInput($toolbar);

        $enablePreviewPane = new Typecho_Widget_Helper_Form_Element_Radio('enablePreviewPane', array('1'=>_t('开启'),'0'=>_t('关闭')), '1', _t('右侧实时预览面板'));
        $form->addInput($enablePreviewPane);
        $enableExport = new Typecho_Widget_Helper_Form_Element_Radio('enableExport', array('1'=>_t('开启'),'0'=>_t('关闭')), '1', _t('导出 HTML/MD 按钮'));
        $form->addInput($enableExport);
        $enableStatusBar = new Typecho_Widget_Helper_Form_Element_Radio('enableStatusBar', array('1'=>_t('开启'),'0'=>_t('关闭')), '1', _t('状态栏（字数/字符/阅读时长）'));
        $form->addInput($enableStatusBar);
        $enableDarkToggle = new Typecho_Widget_Helper_Form_Element_Radio('enableDarkToggle', array('1'=>_t('开启'),'0'=>_t('关闭')), '1', _t('暗色主题开关'));
        $form->addInput($enableDarkToggle);
        $autosyncOnInput = new Typecho_Widget_Helper_Form_Element_Radio('autosyncOnInput', array('1'=>_t('开启'),'0'=>_t('关闭')), '1', _t('实时同步到 textarea（用于预览/提交稳定取值）'));
        $form->addInput($autosyncOnInput);

        $autoLocalize = new Typecho_Widget_Helper_Form_Element_Radio('autoLocalize', array('1'=>_t('开启'),'0'=>_t('关闭')), '1', _t('自动本地化外链图片（保存时抓取到本地）'));
        $form->addInput($autoLocalize);
        $localSubdir = new Typecho_Widget_Helper_Form_Element_Text('localSubdir', NULL, '/usr/uploads/ck5', _t('本地化保存子目录（相对站点根）'));
        $form->addInput($localSubdir);

        $notes = new Typecho_Widget_Helper_Form_Element_Textarea('notes', NULL,
"【使用说明】
1) 默认使用 CDN 加载 CKEditor 5，无需额外操作。
2) 若需本地化：请参见插件包内 README.md（或 GitHub 说明），将 Classic 构建放到 usr/plugins/QDEditorCK5/assets/ckeditor5/classic/ 下，并在此处切换为“本地化”。
3) 粘贴/拖拽/按钮选择图片支持自动上传（action/qdck5-upload）。
4) 保存/预览时可自动抓取外链图片到本地（可开关）。", _t('重要提示'));
        $notes->setAttribute('readonly', 'readonly');
        $form->addInput($notes);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function inject()
    {
        $script = basename($_SERVER['SCRIPT_NAME']);
        if (!in_array($script, array('write-post.php','write-page.php'))) return;

        $options = Helper::options();
        $cfg = $options->plugin('QDEditorCK5');

        $height = isset($cfg->height) ? intval($cfg->height) : 720;
        $useCDN = isset($cfg->useCDN) && $cfg->useCDN == '1';
        $cdnUrl = isset($cfg->cdnUrl) ? $cfg->cdnUrl : '';
        $toolbar = isset($cfg->toolbar) ? $cfg->toolbar : 'heading,bold,italic,underline,strikethrough,link,blockQuote,codeBlock,bulletedList,numberedList,insertTable,undo,redo';
        $enablePreviewPane = isset($cfg->enablePreviewPane) && $cfg->enablePreviewPane == '1';
        $enableExport = isset($cfg->enableExport) && $cfg->enableExport == '1';
        $enableStatusBar = isset($cfg->enableStatusBar) && $cfg->enableStatusBar == '1';
        $enableDarkToggle = isset($cfg->enableDarkToggle) && $cfg->enableDarkToggle == '1';
        $autosyncOnInput = isset($cfg->autosyncOnInput) && $cfg->autosyncOnInput == '1';
        $autoLocalize = isset($cfg->autoLocalize) && $cfg->autoLocalize == '1';
        $localSubdir = isset($cfg->localSubdir) ? $cfg->localSubdir : '/usr/uploads/ck5';
        $uiLang = isset($cfg->uiLang) ? $cfg->uiLang : 'zh-CN';

        $pluginUrl = Typecho_Common::url('usr/plugins/QDEditorCK5', $options->siteUrl);
        $uploadAction = Typecho_Common::url('action/qdck5-upload', $options->index);
        $fetchAction = Typecho_Common::url('action/qdck5-fetch', $options->index);
        $tokenU = Typecho_Widget::widget('Widget_Security')->getToken($uploadAction);
        $tokenF = Typecho_Widget::widget('Widget_Security')->getToken($fetchAction);

        echo '<link rel="stylesheet" href="'.$pluginUrl.'/assets/qdck5.css">';
        echo '<div id="qdck5-brand" class="qdck5-brand" title="QDEditor CK5 by aieii"><svg viewBox="0 0 24 24" width="16" height="16"><path d="M12 2l3 3-3 3-3-3 3-3zm0 14l3 3-3 3-3-3 3-3zM2 12l3-3 3 3-3 3-3-3zm14 0l3-3 3 3-3 3-3-3z" fill="currentColor"/></svg><b>QDEditor CK5</b></div>';

        $conf = array(
            'height' => $height,
            'useCDN' => $useCDN,
            'cdnUrl' => $cdnUrl,
            'toolbar' => array_map('trim', explode(',', $toolbar)),
            'baseUrl' => $pluginUrl,
            'enablePreviewPane' => $enablePreviewPane,
            'enableExport' => $enableExport,
            'enableStatusBar' => $enableStatusBar,
            'enableDarkToggle' => $enableDarkToggle,
            'autosyncOnInput' => $autosyncOnInput,
            'autoLocalize' => $autoLocalize,
            'localSubdir' => $localSubdir,
            'uiLang' => $uiLang,
            'upload' => array('url'=>$uploadAction, 'token'=>$tokenU),
            'fetch' => array('url'=>$fetchAction, 'token'=>$tokenF)
        );
        echo '<script>window.QDCK5_CFG = '.json_encode($conf, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).';</script>';
        echo '<script src="'.$pluginUrl.'/assets/ckeditor5-loader.js"></script>';
    }
}

class QDEditorCK5_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function action()
    {
        $this->widget('Widget_User')->pass('contributor');
        $this->security->protect();
        if (!$this->request->isPost()) {
            $this->response->throwJson(array('error'=>array('message'=>'Invalid method')));
        }
        $file = isset($_FILES['upload']) ? $_FILES['upload'] : (isset($_FILES['file']) ? $_FILES['file'] : null);
        if (!$file || !isset($file['tmp_name'])) {
            $this->response->throwJson(array('error'=>array('message'=>'No file')));
        }
        $allowed = array('image/jpeg','image/png','image/gif','image/webp');
        $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : $file['type'];
        if (!in_array($mime, $allowed)) {
            $this->response->throwJson(array('error'=>array('message'=>'Invalid file type')));
        }
        $root = rtrim(defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__FILE__,4), '/');
        $opts = Helper::options()->plugin('QDEditorCK5');
        $uploadDirCfg = isset($opts->localSubdir) ? $opts->localSubdir : '/usr/uploads/ck5';
        $path = $root . rtrim($uploadDirCfg, '/');
        $sub = date('/Y/m/');
        $dir = rtrim($path, '/') . $sub;
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = uniqid('qd_', true) . ($ext ? ('.' . strtolower($ext)) : '');
        $dest = $dir . $name;
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            $this->response->throwJson(array('error'=>array('message'=>'Move failed')));
        }
        $url = Typecho_Common::url(trim($uploadDirCfg,'/') . $sub . $name, Helper::options()->siteUrl);
        $this->response->throwJson(array('url'=>$url));
    }
}

class QDEditorCK5_Fetch extends Typecho_Widget implements Widget_Interface_Do
{
    public function action()
    {
        $this->widget('Widget_User')->pass('contributor');
        $this->security->protect();
        if (!$this->request->isPost()) {
            $this->response->throwJson(array('ok'=>0,'msg'=>'Invalid method'));
        }
        $url = trim($this->request->get('url'));
        if (!$url || !preg_match('/^https?:\\/\\//i', $url)) {
            $this->response->throwJson(array('ok'=>0,'msg'=>'Invalid url'));
        }
        $opts = Helper::options()->plugin('QDEditorCK5');
        if (!(isset($opts->autoLocalize) && $opts->autoLocalize == '1')) {
            $this->response->throwJson(array('ok'=>0,'msg'=>'autoLocalize disabled'));
        }
        $root = rtrim(defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__FILE__,4), '/');
        $uploadDirCfg = isset($opts->localSubdir) ? $opts->localSubdir : '/usr/uploads/ck5';
        $path = $root . rtrim($uploadDirCfg, '/');
        $sub = date('/Y/m/');
        $dir = rtrim($path, '/') . $sub;
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $ext = '.jpg';
        if (preg_match('/\\.(png|jpe?g|gif|webp)(?:\\?.*)?$/i', $url, $m)) {
            $ext = '.' . strtolower($m[1] == 'jpeg' ? 'jpg' : $m[1]);
        }
        $name = uniqid('qdf_', true) . $ext;
        $dest = $dir . $name;

        $data = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>false));
            $data = curl_exec($ch);
            curl_close($ch);
        } else {
            $data = @file_get_contents($url);
        }
        if (!$data) {
            $this->response->throwJson(array('ok'=>0,'msg'=>'fetch failed'));
        }
        if (!@file_put_contents($dest, $data)) {
            $this->response->throwJson(array('ok'=>0,'msg'=>'save failed'));
        }
        $localUrl = Typecho_Common::url(trim($uploadDirCfg,'/') . $sub . $name, Helper::options()->siteUrl);
        $this->response->throwJson(array('ok'=>1,'url'=>$localUrl));
    }
}

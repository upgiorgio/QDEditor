# QDEditor
因为找不到好用简单的typecho后台编辑器，就自己简单弄了一个
上传到typecho管理目录下的usr/plugins/QDEditorCK5

# QDEditor CK5 for Typecho (CDN 默认)

> 作者：**aieii** · 许可证：MIT  
> Typecho 后台富文本编辑器（基于 **CKEditor 5 Classic**）

- ✅ **CDN 默认加载**（开箱即用）
- ✅ 分栏预览可拖拽 & 同步滚动
- ✅ 粘贴/拖拽图片自动上传（本地接口）
- ✅ 外链图片自动本地化（可开关）
- ✅ 导出 HTML / Markdown
- ✅ 状态栏（字数/字符/阅读时长）
- ✅ 暗色主题
- ✅ 简体 / 繁体 / 英文 多语言 UI
- ✅ 默认编辑区 ≈30 行（720px，可设置）

## 安装
1. 下载 Release 附件的 zip，解压得到 `usr/` 目录。  
2. 将 `usr/` 覆盖到你站点根目录（或仅把 `usr/plugins/QDEditorCK5/` 放到 Typecho 插件目录）。  
3. Typecho 后台 → 插件 → 启用 **QDEditor CK5**。  
4. 打开「插件设置」，按需：切换语言、调整高度与工具栏、开关预览/导出/状态栏/暗色/实时同步、外链图片本地化与本地保存目录等。

## 本地化（可选）
默认使用 CDN。若希望完全离线：
1. 下载对应版本的 CKEditor 5 Classic 构建文件 `ckeditor.js`；  
   可选中文翻译：`translations/zh-cn.js`（简体）、`translations/zh.js`（繁体）。  
2. 放置路径：

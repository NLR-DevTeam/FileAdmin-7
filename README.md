# FileAdmin
由盐鸡开发的一款轻量级文件管理器

## 兼容性
- 服务端：完美兼容PHP 7.x，理论上8.x也可以运行。
- 浏览器：仅兼容Google Chrome / Microsoft Edge的最新版本。

## 安装
- 从Github存储库直接下载fileadmin.php。
- 到你的主机直接上传刚才下载的fileadmin.php。
- 如果认为原文件名不安全，您可以对此文件进行重命名。尽量将其安装在您的网站根目录。
- **[重要]打开此文件，在第一行修改$PASSWORD变量，输入您自己设定的密码。不更改此处设置会导致他人可以随意查看并修改您的文件，非常危险。**

## 使用
FileAdmin为用户定义了多种方便使用的快捷键。
- 在密码输入页面按下“/”以聚焦输入框。
- 在文件管理页面按下“/”以编辑路径。
- 在文件管理页面按下“Ctrl+A”以选中所有文件。
- 在文本编辑器按下“Ctrl+S”以保存文件。

FileAdmin内置了从本仓库获取源码并自动更新本体程序的功能。在任意界面点击左上方的“FileAdmin”字样即可检查更新。**此功能需要您的主机/服务器可以正常连接raw.githubusercontent.com。部分大陆地区的主机可能不支持此功能。**

## 感谢
- [星辰云](https://starxn.com)提供开发环境支持
- [XIAYM](https://github.com/XIAYM-gh)提供开发环境支持
- [AdminX](https://github.com/1689295608/AdminX)提供部分函数实现

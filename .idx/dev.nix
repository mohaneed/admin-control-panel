# .idx/dev.nix - ملف لإجبار النظام على إعادة بناء البيئة
{ pkgs, ... }: {
  # استخدام قناة مستقرة لضمان عدم حدوث مشاكل توافق
  channel = "stable-23.11";

  # الحزم الأساسية التي يحتاجها المشروع
  packages = [
    pkgs.php                  # لغة PHP
    pkgs.php82Packages.composer # مدير الحزم Composer
    pkgs.git
  ];

  # إعدادات بيئة العمل
  idx = {
    extensions = [
      "bmewburn.vscode-intelephense-client" # إضافة لدعم PHP
    ];

    # أوامر تعمل تلقائياً عند بناء البيئة (اختياري)
    workspace = {
      onCreate = {
        # default-install = "composer install";
      };
    };
  };
}
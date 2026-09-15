import 'dart:convert';

BasicSettingsModel basicSettingsModelFromJson(String str) =>
    BasicSettingsModel.fromJson(json.decode(str));

String basicSettingsModelToJson(BasicSettingsModel data) =>
    json.encode(data.toJson());

class BasicSettingsModel {
  Message? message;
  Data? data;
  String? type;

  BasicSettingsModel({
    this.message,
    this.data,
    this.type,
  });

  factory BasicSettingsModel.fromJson(Map<String, dynamic> json) =>
      BasicSettingsModel(
        message:
            json["message"] == null ? null : Message.fromJson(json["message"]),
        data: json["data"] == null ? null : Data.fromJson(json["data"]),
        type: json["type"],
      );

  Map<String, dynamic> toJson() => {
        "message": message?.toJson(),
        "data": data?.toJson(),
        "type": type,
      };
}

class Data {
  BasicSettings? basicSettings;
  ReferralSettings? referralSettings;
  BaseCur? baseCur;
  WebLinks? webLinks;
  List<Language>? languages;
  SplashScreen? splashScreen;
  List<OnboardScreen>? onboardScreens;
  ImagePaths? imagePaths;
  AppImagePaths? appImagePaths;

  Data({
    this.basicSettings,
    this.referralSettings,
    this.baseCur,
    this.webLinks,
    this.languages,
    this.splashScreen,
    this.onboardScreens,
    this.imagePaths,
    this.appImagePaths,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        basicSettings: json["basic_settings"] == null
            ? null
            : BasicSettings.fromJson(json["basic_settings"]),
        referralSettings: json["referral_settings"] == null
            ? null
            : ReferralSettings.fromJson(json["referral_settings"]),
        baseCur: json["base_cur"] == null
            ? null
            : BaseCur.fromJson(json["base_cur"]),
        webLinks: json["web_links"] == null
            ? null
            : WebLinks.fromJson(json["web_links"]),
        languages: json["languages"] == null
            ? []
            : List<Language>.from(
                json["languages"]!.map((x) => Language.fromJson(x))),
        splashScreen: json["splash_screen"] == null
            ? null
            : SplashScreen.fromJson(json["splash_screen"]),
        onboardScreens: json["onboard_screens"] == null
            ? []
            : List<OnboardScreen>.from(
                json["onboard_screens"]!.map((x) => OnboardScreen.fromJson(x))),
        imagePaths: json["image_paths"] == null
            ? null
            : ImagePaths.fromJson(json["image_paths"]),
        appImagePaths: json["app_image_paths"] == null
            ? null
            : AppImagePaths.fromJson(json["app_image_paths"]),
      );

  Map<String, dynamic> toJson() => {
        "basic_settings": basicSettings?.toJson(),
        "referral_settings": referralSettings?.toJson(),
        "base_cur": baseCur?.toJson(),
        "web_links": webLinks?.toJson(),
        "languages": languages == null
            ? []
            : List<dynamic>.from(languages!.map((x) => x.toJson())),
        "splash_screen": splashScreen?.toJson(),
        "onboard_screens": onboardScreens == null
            ? []
            : List<dynamic>.from(onboardScreens!.map((x) => x.toJson())),
        "image_paths": imagePaths?.toJson(),
        "app_image_paths": appImagePaths?.toJson(),
      };
}

class AppImagePaths {
  String? baseUrl;
  String? pathLocation;
  String? defaultImage;

  AppImagePaths({
    this.baseUrl,
    this.pathLocation,
    this.defaultImage,
  });

  factory AppImagePaths.fromJson(Map<String, dynamic> json) => AppImagePaths(
        baseUrl: json["base_url"],
        pathLocation: json["path_location"],
        defaultImage: json["default_image"],
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "path_location": pathLocation,
        "default_image": defaultImage,
      };
}

class BaseCur {
  int? id;
  String? code;
  String? symbol;
  int? rate;
  bool? both;
  bool? senderCurrency;
  bool? receiverCurrency;

  BaseCur({
    this.id,
    this.code,
    this.symbol,
    this.rate,
    this.both,
    this.senderCurrency,
    this.receiverCurrency,
  });

  factory BaseCur.fromJson(Map<String, dynamic> json) => BaseCur(
        id: json["id"],
        code: json["code"],
        symbol: json["symbol"],
        rate: json["rate"],
        both: json["both"],
        senderCurrency: json["senderCurrency"],
        receiverCurrency: json["receiverCurrency"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "code": code,
        "symbol": symbol,
        "rate": rate,
        "both": both,
        "senderCurrency": senderCurrency,
        "receiverCurrency": receiverCurrency,
      };
}

class BasicSettings {
  int? id;
  String? siteName;
  String? siteTitle;
  String? baseColor;
  String? secondaryColor;
  int? otpExpSeconds;
  String? timezone;
  int? userRegistration;
  int? securePassword;
  int? agreePolicy;
  int? forceSsl;
  int? emailVerification;
  int? emailNotification;
  int? pushNotification;
  int? kycVerification;
  String? siteLogoDark;
  String? siteLogo;
  String? siteFavDark;
  String? siteFav;
  dynamic preloaderImage;
  MailConfig? mailConfig;
  dynamic mailActivity;
  PushNotificationConfig? pushNotificationConfig;
  dynamic pushNotificationActivity;
  BroadcastConfig? broadcastConfig;
  dynamic broadcastActivity;
  dynamic smsConfig;
  dynamic smsActivity;
  String? webVersion;
  String? adminVersion;
  DateTime? createdAt;
  DateTime? updatedAt;
  bool? userKycStatus;

  BasicSettings({
    this.id,
    this.siteName,
    this.siteTitle,
    this.baseColor,
    this.secondaryColor,
    this.otpExpSeconds,
    this.timezone,
    this.userRegistration,
    this.securePassword,
    this.agreePolicy,
    this.forceSsl,
    this.emailVerification,
    this.emailNotification,
    this.pushNotification,
    this.kycVerification,
    this.siteLogoDark,
    this.siteLogo,
    this.siteFavDark,
    this.siteFav,
    this.preloaderImage,
    this.mailConfig,
    this.mailActivity,
    this.pushNotificationConfig,
    this.pushNotificationActivity,
    this.broadcastConfig,
    this.broadcastActivity,
    this.smsConfig,
    this.smsActivity,
    this.webVersion,
    this.adminVersion,
    this.createdAt,
    this.updatedAt,
    this.userKycStatus,
  });

  factory BasicSettings.fromJson(Map<String, dynamic> json) => BasicSettings(
        id: json["id"],
        siteName: json["site_name"],
        siteTitle: json["site_title"],
        baseColor: json["base_color"],
        secondaryColor: json["secondary_color"],
        otpExpSeconds: json["otp_exp_seconds"],
        timezone: json["timezone"],
        userRegistration: json["user_registration"],
        securePassword: json["secure_password"],
        agreePolicy: json["agree_policy"],
        forceSsl: json["force_ssl"],
        emailVerification: json["email_verification"],
        emailNotification: json["email_notification"],
        pushNotification: json["push_notification"],
        kycVerification: json["kyc_verification"],
        siteLogoDark: json["site_logo_dark"],
        siteLogo: json["site_logo"],
        siteFavDark: json["site_fav_dark"],
        siteFav: json["site_fav"],
        preloaderImage: json["preloader_image"],
        mailConfig: json["mail_config"] == null
            ? null
            : MailConfig.fromJson(json["mail_config"]),
        mailActivity: json["mail_activity"],
        pushNotificationConfig: json["push_notification_config"] == null
            ? null
            : PushNotificationConfig.fromJson(json["push_notification_config"]),
        pushNotificationActivity: json["push_notification_activity"],
        broadcastConfig: json["broadcast_config"] == null
            ? null
            : BroadcastConfig.fromJson(json["broadcast_config"]),
        broadcastActivity: json["broadcast_activity"],
        smsConfig: json["sms_config"],
        smsActivity: json["sms_activity"],
        webVersion: json["web_version"],
        adminVersion: json["admin_version"],
        createdAt: json["created_at"] == null
            ? null
            : DateTime.parse(json["created_at"]),
        updatedAt: json["updated_at"] == null
            ? null
            : DateTime.parse(json["updated_at"]),
        userKycStatus: json["user_kyc_status"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "site_name": siteName,
        "site_title": siteTitle,
        "base_color": baseColor,
        "secondary_color": secondaryColor,
        "otp_exp_seconds": otpExpSeconds,
        "timezone": timezone,
        "user_registration": userRegistration,
        "secure_password": securePassword,
        "agree_policy": agreePolicy,
        "force_ssl": forceSsl,
        "email_verification": emailVerification,
        "email_notification": emailNotification,
        "push_notification": pushNotification,
        "kyc_verification": kycVerification,
        "site_logo_dark": siteLogoDark,
        "site_logo": siteLogo,
        "site_fav_dark": siteFavDark,
        "site_fav": siteFav,
        "preloader_image": preloaderImage,
        "mail_config": mailConfig?.toJson(),
        "mail_activity": mailActivity,
        "push_notification_config": pushNotificationConfig?.toJson(),
        "push_notification_activity": pushNotificationActivity,
        "broadcast_config": broadcastConfig?.toJson(),
        "broadcast_activity": broadcastActivity,
        "sms_config": smsConfig,
        "sms_activity": smsActivity,
        "web_version": webVersion,
        "admin_version": adminVersion,
        "created_at": createdAt?.toIso8601String(),
        "updated_at": updatedAt?.toIso8601String(),
        "user_kyc_status": userKycStatus,
      };
}

class BroadcastConfig {
  String? method;
  String? appId;
  String? primaryKey;
  String? secretKey;
  String? cluster;

  BroadcastConfig({
    this.method,
    this.appId,
    this.primaryKey,
    this.secretKey,
    this.cluster,
  });

  factory BroadcastConfig.fromJson(Map<String, dynamic> json) =>
      BroadcastConfig(
        method: json["method"],
        appId: json["app_id"],
        primaryKey: json["primary_key"],
        secretKey: json["secret_key"],
        cluster: json["cluster"],
      );

  Map<String, dynamic> toJson() => {
        "method": method,
        "app_id": appId,
        "primary_key": primaryKey,
        "secret_key": secretKey,
        "cluster": cluster,
      };
}

class MailConfig {
  String? method;
  String? host;
  String? port;
  String? encryption;
  String? username;
  String? password;
  String? from;
  String? appName;

  MailConfig({
    this.method,
    this.host,
    this.port,
    this.encryption,
    this.username,
    this.password,
    this.from,
    this.appName,
  });

  factory MailConfig.fromJson(Map<String, dynamic> json) => MailConfig(
        method: json["method"],
        host: json["host"],
        port: json["port"],
        encryption: json["encryption"],
        username: json["username"],
        password: json["password"],
        from: json["from"],
        appName: json["app_name"],
      );

  Map<String, dynamic> toJson() => {
        "method": method,
        "host": host,
        "port": port,
        "encryption": encryption,
        "username": username,
        "password": password,
        "from": from,
        "app_name": appName,
      };
}

class PushNotificationConfig {
  String? method;
  String? instanceId;
  String? primaryKey;

  PushNotificationConfig({
    this.method,
    this.instanceId,
    this.primaryKey,
  });

  factory PushNotificationConfig.fromJson(Map<String, dynamic> json) =>
      PushNotificationConfig(
        method: json["method"],
        instanceId: json["instance_id"],
        primaryKey: json["primary_key"],
      );

  Map<String, dynamic> toJson() => {
        "method": method,
        "instance_id": instanceId,
        "primary_key": primaryKey,
      };
}

class ImagePaths {
  String? basePath;
  String? pathLocation;
  String? defaultImage;

  ImagePaths({
    this.basePath,
    this.pathLocation,
    this.defaultImage,
  });

  factory ImagePaths.fromJson(Map<String, dynamic> json) => ImagePaths(
        basePath: json["base_path"],
        pathLocation: json["path_location"],
        defaultImage: json["default_image"],
      );

  Map<String, dynamic> toJson() => {
        "base_path": basePath,
        "path_location": pathLocation,
        "default_image": defaultImage,
      };
}

class Language {
  int? id;
  String? name;
  String? code;
  bool? status;

  Language({
    this.id,
    this.name,
    this.code,
    this.status,
  });

  factory Language.fromJson(Map<String, dynamic> json) => Language(
        id: json["id"],
        name: json["name"],
        code: json["code"],
        status: json["status"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "code": code,
        "status": status,
      };
}

class OnboardScreen {
  String? title;
  dynamic subTitle;
  String? image;
  int? status;

  OnboardScreen({
    this.title,
    this.subTitle,
    this.image,
    this.status,
  });

  factory OnboardScreen.fromJson(Map<String, dynamic> json) => OnboardScreen(
        title: json["title"],
        subTitle: json["sub_title"],
        image: json["image"],
        status: json["status"],
      );

  Map<String, dynamic> toJson() => {
        "title": title,
        "sub_title": subTitle,
        "image": image,
        "status": status,
      };
}

class ReferralSettings {
  int? id;
  double? bonus;
  String? walletType;
  int? mail;
  int? status;
  DateTime? createdAt;
  DateTime? updatedAt;

  ReferralSettings({
    this.id,
    this.bonus,
    this.walletType,
    this.mail,
    this.status,
    this.createdAt,
    this.updatedAt,
  });

  factory ReferralSettings.fromJson(Map<String, dynamic> json) =>
      ReferralSettings(
        id: json["id"],
        bonus: json["bonus"]?.toDouble(),
        walletType: json["wallet_type"],
        mail: json["mail"],
        status: json["status"],
        createdAt: json["created_at"] == null
            ? null
            : DateTime.parse(json["created_at"]),
        updatedAt: json["updated_at"] == null
            ? null
            : DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "bonus": bonus,
        "wallet_type": walletType,
        "mail": mail,
        "status": status,
        "created_at": createdAt?.toIso8601String(),
        "updated_at": updatedAt?.toIso8601String(),
      };
}

class SplashScreen {
  String? image;
  String? version;

  SplashScreen({
    this.image,
    this.version,
  });

  factory SplashScreen.fromJson(Map<String, dynamic> json) => SplashScreen(
        image: json["image"],
        version: json["version"],
      );

  Map<String, dynamic> toJson() => {
        "image": image,
        "version": version,
      };
}

class WebLinks {
  String? privacyPolicy;
  String? aboutUs;
  String? contactUs;

  WebLinks({
    this.privacyPolicy,
    this.aboutUs,
    this.contactUs,
  });

  factory WebLinks.fromJson(Map<String, dynamic> json) => WebLinks(
        privacyPolicy: json["privacy-policy"],
        aboutUs: json["about-us"],
        contactUs: json["contact-us"],
      );

  Map<String, dynamic> toJson() => {
        "privacy-policy": privacyPolicy,
        "about-us": aboutUs,
        "contact-us": contactUs,
      };
}

class Message {
  List<String>? success;

  Message({
    this.success,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: json["success"] == null
            ? []
            : List<String>.from(json["success"]!.map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success":
            success == null ? [] : List<dynamic>.from(success!.map((x) => x)),
      };
}

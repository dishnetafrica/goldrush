import 'dart:convert';

NotificationModel notificationModelFromJson(String str) =>
    NotificationModel.fromJson(json.decode(str));

String notificationModelToJson(NotificationModel data) =>
    json.encode(data.toJson());

class NotificationModel {
  NotificationModelMessage message;
  Data data;
  String type;

  NotificationModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) =>
      NotificationModel(
        message: NotificationModelMessage.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
        type: json["type"],
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
        "type": type,
      };
}

class Data {
  List<Notification>? notifications;

  Data({
    this.notifications,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        notifications: json["notifications"] != null
            ? List<Notification>.from(
                json["notifications"].map((x) => Notification.fromJson(x)))
            : null,
      );

  Map<String, dynamic> toJson() => {
        "notifications": notifications != null
            ? List<dynamic>.from(notifications!.map((x) => x.toJson()))
            : null,
      };
}

class Notification {
  int id;
  int userId;
  String type;
  NotificationMessage message;
  DateTime createdAt;
  DateTime updatedAt;
  User user;

  Notification({
    required this.id,
    required this.userId,
    required this.type,
    required this.message,
    required this.createdAt,
    required this.updatedAt,
    required this.user,
  });

  factory Notification.fromJson(Map<String, dynamic> json) => Notification(
        id: json["id"],
        userId: json["user_id"],
        type: json["type"],
        message: NotificationMessage.fromJson(json["message"]),
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        user: User.fromJson(json["user"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "user_id": userId,
        "type": type,
        "message": message.toJson(),
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "user": user.toJson(),
      };
}

class NotificationMessage {
  String title;
  String message;
  String image;

  NotificationMessage({
    required this.title,
    required this.message,
    required this.image,
  });

  factory NotificationMessage.fromJson(Map<String, dynamic> json) =>
      NotificationMessage(
        title: json["title"],
        message: json["message"],
        image: json["image"],
      );

  Map<String, dynamic> toJson() => {
        "title": title,
        "message": message,
        "image": image,
      };
}

class User {
  int id;
  String firstname;
  String lastname;
  String username;
  String email;
  dynamic mobileCode;
  dynamic mobile;
  dynamic fullMobile;
  String referralId;
  int currentReferralLevelId;
  dynamic image;
  int status;
  Address? address;
  int emailVerified;
  int kycVerified;
  dynamic verCode;
  dynamic verCodeSendAt;
  int twoFactorVerified;
  int twoFactorStatus;
  dynamic emailVerifiedAt;
  dynamic deletedAt;
  DateTime createdAt;
  DateTime? updatedAt;
  String fullname;
  String userImage;
  StringStatus stringStatus;
  String lastLogin;
  StringStatus kycStringStatus;

  User({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.username,
    required this.email,
    this.mobileCode,
    this.mobile,
    this.fullMobile,
    required this.referralId,
    required this.currentReferralLevelId,
    this.image,
    required this.status,
    this.address,
    required this.emailVerified,
    required this.kycVerified,
    required this.verCode,
    required this.verCodeSendAt,
    required this.twoFactorVerified,
    required this.twoFactorStatus,
    required this.emailVerifiedAt,
    required this.deletedAt,
    required this.createdAt,
    this.updatedAt,
    required this.fullname,
    required this.userImage,
    required this.stringStatus,
    required this.lastLogin,
    required this.kycStringStatus,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json["id"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        username: json["username"],
        email: json["email"],
        mobileCode: json["mobile_code"] ?? "",
        mobile: json["mobile"] ?? "",
        fullMobile: json["full_mobile"] ?? "",
        referralId: json["referral_id"],
        currentReferralLevelId: json["current_referral_level_id"],
        image: json["image"] ?? "",
        status: json["status"],
        address:
            json["address"] != null ? Address.fromJson(json["address"]) : null,
        emailVerified: json["email_verified"],
        kycVerified: json["kyc_verified"],
        verCode: json["ver_code"],
        verCodeSendAt: json["ver_code_send_at"],
        twoFactorVerified: json["two_factor_verified"],
        twoFactorStatus: json["two_factor_status"],
        emailVerifiedAt: json["email_verified_at"],
        deletedAt: json["deleted_at"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: json["updated_at"] != null
            ? DateTime.parse(json["updated_at"])
            : null,
        fullname: json["fullname"],
        userImage: json["userImage"],
        stringStatus: StringStatus.fromJson(json["stringStatus"]),
        lastLogin: json["lastLogin"],
        kycStringStatus: StringStatus.fromJson(json["kycStringStatus"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "firstname": firstname,
        "lastname": lastname,
        "username": username,
        "email": email,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "full_mobile": fullMobile,
        "referral_id": referralId,
        "current_referral_level_id": currentReferralLevelId,
        "image": image,
        "status": status,
        "address": address?.toJson(),
        "email_verified": emailVerified,
        "kyc_verified": kycVerified,
        "ver_code": verCode,
        "ver_code_send_at": verCodeSendAt,
        "two_factor_verified": twoFactorVerified,
        "two_factor_status": twoFactorStatus,
        "email_verified_at": emailVerifiedAt,
        "deleted_at": deletedAt,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt?.toIso8601String(),
        "fullname": fullname,
        "userImage": userImage,
        "stringStatus": stringStatus.toJson(),
        "lastLogin": lastLogin,
        "kycStringStatus": kycStringStatus.toJson(),
      };
}

class Address {
  String country;
  String state;
  String city;
  String? zip;
  String? address;

  Address({
    required this.country,
    required this.state,
    required this.city,
    this.zip,
    this.address,
  });

  // Factory method for creating Address from JSON
  factory Address.fromJson(Map<String, dynamic> json) => Address(
        country: json["country"],
        state: json["state"],
        city: json["city"],
        zip: json["zip"],
        address: json["address"],
      );

  // Method to convert Address instance to JSON
  Map<String, dynamic> toJson() => {
        "country": country,
        "state": state,
        "city": city,
        "zip": zip,
        "address": address,
      };
}

class StringStatus {
  String stringStatusClass;
  String value;

  StringStatus({
    required this.stringStatusClass,
    required this.value,
  });

  factory StringStatus.fromJson(Map<String, dynamic> json) => StringStatus(
        stringStatusClass: json["class"],
        value: json["value"],
      );

  Map<String, dynamic> toJson() => {
        "class": stringStatusClass,
        "value": value,
      };
}

class NotificationModelMessage {
  List<String> success;

  NotificationModelMessage({
    required this.success,
  });

  factory NotificationModelMessage.fromJson(Map<String, dynamic> json) =>
      NotificationModelMessage(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}

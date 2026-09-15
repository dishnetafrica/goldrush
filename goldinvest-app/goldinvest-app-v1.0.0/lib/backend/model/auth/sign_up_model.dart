import 'dart:convert';

SignUpModel signUpModelFromJson(String str) =>
    SignUpModel.fromJson(json.decode(str));

String signUpModelToJson(SignUpModel data) => json.encode(data.toJson());

class SignUpModel {
  Message message;
  Data data;
  String type;

  SignUpModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory SignUpModel.fromJson(Map<String, dynamic> json) => SignUpModel(
        message: Message.fromJson(json["message"]),
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
  String token;
  UserInfo userInfo;
  Authorization authorization;

  Data({
    required this.token,
    required this.userInfo,
    required this.authorization,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        token: json["token"],
        userInfo: UserInfo.fromJson(json["user_info"]),
        authorization: Authorization.fromJson(json["authorization"]),
      );

  Map<String, dynamic> toJson() => {
        "token": token,
        "user_info": userInfo.toJson(),
        "authorization": authorization.toJson(),
      };
}

class Authorization {
  String token;

  Authorization({
    required this.token,
  });

  factory Authorization.fromJson(Map<String, dynamic> json) => Authorization(
        token: json["token"],
      );

  Map<String, dynamic> toJson() => {
        "token": token,
      };
}

class UserInfo {
  int id;
  String firstname;
  String lastname;
  String fullname;
  String username;
  String email;
  dynamic mobileCode;
  dynamic mobile;
  dynamic fullMobile;
  int emailVerified;
  int kycVerified;
  int twoFactorVerified;
  int twoFactorStatus;
  dynamic twoFactorSecret;
  String referralId;
  dynamic currentReferralLevelId;

  UserInfo({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.fullname,
    required this.username,
    required this.email,
    required this.mobileCode,
    required this.mobile,
    required this.fullMobile,
    required this.emailVerified,
    required this.kycVerified,
    required this.twoFactorVerified,
    required this.twoFactorStatus,
    required this.twoFactorSecret,
    required this.referralId,
    required this.currentReferralLevelId,
  });

  factory UserInfo.fromJson(Map<String, dynamic> json) => UserInfo(
        id: json["id"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        fullname: json["fullname"],
        username: json["username"],
        email: json["email"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        fullMobile: json["full_mobile"],
        emailVerified: json["email_verified"],
        kycVerified: json["kyc_verified"],
        twoFactorVerified: json["two_factor_verified"],
        twoFactorStatus: json["two_factor_status"],
        twoFactorSecret: json["two_factor_secret"],
        referralId: json["referral_id"],
        currentReferralLevelId: json["current_referral_level_id"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "firstname": firstname,
        "lastname": lastname,
        "fullname": fullname,
        "username": username,
        "email": email,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "full_mobile": fullMobile,
        "email_verified": emailVerified,
        "kyc_verified": kycVerified,
        "two_factor_verified": twoFactorVerified,
        "two_factor_status": twoFactorStatus,
        "two_factor_secret": twoFactorSecret,
        "referral_id": referralId,
        "current_referral_level_id": currentReferralLevelId,
      };
}

class Message {
  List<String> success;

  Message({
    required this.success,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}

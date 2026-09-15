import 'dart:convert';

MyStatusModel myStatusModelFromJson(String str) =>
    MyStatusModel.fromJson(json.decode(str));

String myStatusModelToJson(MyStatusModel data) => json.encode(data.toJson());

class MyStatusModel {
  Message message;
  Data data;
  String type;

  MyStatusModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory MyStatusModel.fromJson(Map<String, dynamic> json) => MyStatusModel(
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
  int totalRefers;
  int totalInvestment;
  String level;
  String referCode;
  String referLink;
  ReferUsers referUsers;

  Data({
    required this.totalRefers,
    required this.totalInvestment,
    required this.level,
    required this.referCode,
    required this.referLink,
    required this.referUsers,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        totalRefers: json["total_refers"],
        totalInvestment: json["total_investment"],
        level: json["level"],
        referCode: json["refer_code"],
        referLink: json["refer_link"],
        referUsers: ReferUsers.fromJson(json["refer_users"]),
      );

  Map<String, dynamic> toJson() => {
        "total_refers": totalRefers,
        "total_investment": totalInvestment,
        "level": level,
        "refer_code": referCode,
        "refer_link": referLink,
        "refer_users": referUsers.toJson(),
      };
}

class ReferUsers {
  int currentPage;
  List<Datum>? data;
  String firstPageUrl;
  int? from;
  int lastPage;
  String lastPageUrl;
  List<Link> links;
  dynamic nextPageUrl;
  String path;
  int perPage;
  dynamic prevPageUrl;
  int? to;
  int total;

  ReferUsers({
    required this.currentPage,
    this.data,
    required this.firstPageUrl,
    required this.from,
    required this.lastPage,
    required this.lastPageUrl,
    required this.links,
    required this.nextPageUrl,
    required this.path,
    required this.perPage,
    required this.prevPageUrl,
    required this.to,
    required this.total,
  });

  factory ReferUsers.fromJson(Map<String, dynamic> json) => ReferUsers(
        currentPage: json["current_page"],
        data: json["data"] != null
            ? List<Datum>.from(json["data"].map((x) => Datum.fromJson(x)))
            : null,
        firstPageUrl: json["first_page_url"],
        from: json["from"],
        lastPage: json["last_page"],
        lastPageUrl: json["last_page_url"],
        links: List<Link>.from(json["links"].map((x) => Link.fromJson(x))),
        nextPageUrl: json["next_page_url"],
        path: json["path"],
        perPage: json["per_page"],
        prevPageUrl: json["prev_page_url"],
        to: json["to"],
        total: json["total"],
      );

  Map<String, dynamic> toJson() => {
        "current_page": currentPage,
        "data": data != null
            ? List<dynamic>.from(data!.map((x) => x.toJson()))
            : null,
        "first_page_url": firstPageUrl,
        "from": from,
        "last_page": lastPage,
        "last_page_url": lastPageUrl,
        "links": List<dynamic>.from(links.map((x) => x.toJson())),
        "next_page_url": nextPageUrl,
        "path": path,
        "per_page": perPage,
        "prev_page_url": prevPageUrl,
        "to": to,
        "total": total,
      };
}

class Datum {
  int id;
  int referUserId;
  int newUserId;
  DateTime createdAt;
  DateTime updatedAt;
  User user;

  Datum({
    required this.id,
    required this.referUserId,
    required this.newUserId,
    required this.createdAt,
    required this.updatedAt,
    required this.user,
  });

  factory Datum.fromJson(Map<String, dynamic> json) => Datum(
        id: json["id"],
        referUserId: json["refer_user_id"],
        newUserId: json["new_user_id"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        user: User.fromJson(json["user"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "refer_user_id": referUserId,
        "new_user_id": newUserId,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "user": user.toJson(),
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
  dynamic address;
  int emailVerified;
  int kycVerified;
  dynamic verCode;
  dynamic verCodeSendAt;
  int twoFactorVerified;
  int twoFactorStatus;
  dynamic twoFactorSecret;
  dynamic emailVerifiedAt;
  dynamic deletedAt;
  DateTime createdAt;
  DateTime updatedAt;
  String fullname;
  String userImage;
  StringStatus stringStatus;
  String lastLogin;
  StringStatus kycStringStatus;
  List<dynamic> referUsers;

  User({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.username,
    required this.email,
    required this.mobileCode,
    required this.mobile,
    required this.fullMobile,
    required this.referralId,
    required this.currentReferralLevelId,
    required this.image,
    required this.status,
    required this.address,
    required this.emailVerified,
    required this.kycVerified,
    required this.verCode,
    required this.verCodeSendAt,
    required this.twoFactorVerified,
    required this.twoFactorStatus,
    required this.twoFactorSecret,
    required this.emailVerifiedAt,
    required this.deletedAt,
    required this.createdAt,
    required this.updatedAt,
    required this.fullname,
    required this.userImage,
    required this.stringStatus,
    required this.lastLogin,
    required this.kycStringStatus,
    required this.referUsers,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json["id"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        username: json["username"],
        email: json["email"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        fullMobile: json["full_mobile"],
        referralId: json["referral_id"],
        currentReferralLevelId: json["current_referral_level_id"],
        image: json["image"],
        status: json["status"],
        address: json["address"],
        emailVerified: json["email_verified"],
        kycVerified: json["kyc_verified"],
        verCode: json["ver_code"],
        verCodeSendAt: json["ver_code_send_at"],
        twoFactorVerified: json["two_factor_verified"],
        twoFactorStatus: json["two_factor_status"],
        twoFactorSecret: json["two_factor_secret"],
        emailVerifiedAt: json["email_verified_at"],
        deletedAt: json["deleted_at"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        fullname: json["fullname"],
        userImage: json["userImage"],
        stringStatus: StringStatus.fromJson(json["stringStatus"]),
        lastLogin: json["lastLogin"],
        kycStringStatus: StringStatus.fromJson(json["kycStringStatus"]),
        referUsers: List<dynamic>.from(json["refer_users"].map((x) => x)),
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
        "address": address,
        "email_verified": emailVerified,
        "kyc_verified": kycVerified,
        "ver_code": verCode,
        "ver_code_send_at": verCodeSendAt,
        "two_factor_verified": twoFactorVerified,
        "two_factor_status": twoFactorStatus,
        "two_factor_secret": twoFactorSecret,
        "email_verified_at": emailVerifiedAt,
        "deleted_at": deletedAt,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "fullname": fullname,
        "userImage": userImage,
        "stringStatus": stringStatus.toJson(),
        "lastLogin": lastLogin,
        "kycStringStatus": kycStringStatus.toJson(),
        "refer_users": List<dynamic>.from(referUsers.map((x) => x)),
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

class Link {
  String? url;
  String label;
  bool active;

  Link({
    required this.url,
    required this.label,
    required this.active,
  });

  factory Link.fromJson(Map<String, dynamic> json) => Link(
        url: json["url"],
        label: json["label"],
        active: json["active"],
      );

  Map<String, dynamic> toJson() => {
        "url": url,
        "label": label,
        "active": active,
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

import 'dart:convert';

DashboardModel dashboardModelFromJson(String str) =>
    DashboardModel.fromJson(json.decode(str));

String dashboardModelToJson(DashboardModel data) => json.encode(data.toJson());

class DashboardModel {
  Message message;
  Data data;
  String type;

  DashboardModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory DashboardModel.fromJson(Map<String, dynamic> json) => DashboardModel(
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
  Instructions instructions;
  DataUserInfo userInfo;
  List<Wallet> wallets;
  dynamic investAmount;
  dynamic profitAmount;
  List<dynamic> recentTransactions;
  ChartData chartData;
  ProfileImagePaths profileImagePaths;

  Data({
    required this.instructions,
    required this.userInfo,
    required this.wallets,
    required this.investAmount,
    required this.profitAmount,
    required this.recentTransactions,
    required this.chartData,
    required this.profileImagePaths,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        instructions: Instructions.fromJson(json["instructions"]),
        userInfo: DataUserInfo.fromJson(json["user_info"]),
        wallets:
            List<Wallet>.from(json["wallets"].map((x) => Wallet.fromJson(x))),
        investAmount: json["invest_amount"],
        profitAmount: json["profit_amount"],
        recentTransactions:
            List<dynamic>.from(json["recent_transactions"].map((x) => x)),
        chartData: ChartData.fromJson(json["chart_data"]),
        profileImagePaths:
            ProfileImagePaths.fromJson(json["profile_image_paths"]),
      );

  Map<String, dynamic> toJson() => {
        "instructions": instructions.toJson(),
        "user_info": userInfo.toJson(),
        "wallets": List<dynamic>.from(wallets.map((x) => x.toJson())),
        "invest_amount": investAmount,
        "profit_amount": profitAmount,
        "recent_transactions":
            List<dynamic>.from(recentTransactions.map((x) => x)),
        "chart_data": chartData.toJson(),
        "profile_image_paths": profileImagePaths.toJson(),
      };
}

class ChartData {
  List<Statistic> addMoneyStatistics;
  List<Statistic> moneyOutStatistics;

  ChartData({
    required this.addMoneyStatistics,
    required this.moneyOutStatistics,
  });

  factory ChartData.fromJson(Map<String, dynamic> json) => ChartData(
        addMoneyStatistics: List<Statistic>.from(
            json["add_money_statistics"].map((x) => Statistic.fromJson(x))),
        moneyOutStatistics: List<Statistic>.from(
            json["money_out_statistics"].map((x) => Statistic.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "add_money_statistics":
            List<dynamic>.from(addMoneyStatistics.map((x) => x.toJson())),
        "money_out_statistics":
            List<dynamic>.from(moneyOutStatistics.map((x) => x.toJson())),
      };
}

class Statistic {
  DateTime timestamp;
  String value;

  Statistic({
    required this.timestamp,
    required this.value,
  });

  factory Statistic.fromJson(Map<String, dynamic> json) => Statistic(
        timestamp: DateTime.parse(json["timestamp"]),
        value: json["value"],
      );

  Map<String, dynamic> toJson() => {
        "timestamp": timestamp.toIso8601String(),
        "value": value,
      };
}

class Instructions {
  List<String> transactionTypes;
  RecentTransactions recentTransactions;
  InstructionsUserInfo userInfo;

  Instructions({
    required this.transactionTypes,
    required this.recentTransactions,
    required this.userInfo,
  });

  factory Instructions.fromJson(Map<String, dynamic> json) => Instructions(
        transactionTypes:
            List<String>.from(json["transaction_types"].map((x) => x)),
        recentTransactions:
            RecentTransactions.fromJson(json["recent_transactions"]),
        userInfo: InstructionsUserInfo.fromJson(json["user_info"]),
      );

  Map<String, dynamic> toJson() => {
        "transaction_types": List<dynamic>.from(transactionTypes.map((x) => x)),
        "recent_transactions": recentTransactions.toJson(),
        "user_info": userInfo.toJson(),
      };
}

class RecentTransactions {
  String status;

  RecentTransactions({
    required this.status,
  });

  factory RecentTransactions.fromJson(Map<String, dynamic> json) =>
      RecentTransactions(
        status: json["status"],
      );

  Map<String, dynamic> toJson() => {
        "status": status,
      };
}

class InstructionsUserInfo {
  String kycVerified;

  InstructionsUserInfo({
    required this.kycVerified,
  });

  factory InstructionsUserInfo.fromJson(Map<String, dynamic> json) =>
      InstructionsUserInfo(
        kycVerified: json["kyc_verified"],
      );

  Map<String, dynamic> toJson() => {
        "kyc_verified": kycVerified,
      };
}

class ProfileImagePaths {
  String baseUrl;
  String pathLocation;
  String defaultImage;

  ProfileImagePaths({
    required this.baseUrl,
    required this.pathLocation,
    required this.defaultImage,
  });

  factory ProfileImagePaths.fromJson(Map<String, dynamic> json) =>
      ProfileImagePaths(
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

class DataUserInfo {
  int id;
  String firstname;
  String lastname;
  String fullname;
  String username;
  String email;
  dynamic image;
  dynamic mobileCode;
  dynamic mobile;
  dynamic fullMobile;
  int emailVerified;
  int kycVerified;
  int twoFactorVerified;
  int twoFactorStatus;
  dynamic twoFactorSecret;

  DataUserInfo({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.fullname,
    required this.username,
    required this.email,
    required this.image,
    required this.mobileCode,
    required this.mobile,
    required this.fullMobile,
    required this.emailVerified,
    required this.kycVerified,
    required this.twoFactorVerified,
    required this.twoFactorStatus,
    required this.twoFactorSecret,
  });

  factory DataUserInfo.fromJson(Map<String, dynamic> json) => DataUserInfo(
        id: json["id"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        fullname: json["fullname"],
        username: json["username"],
        email: json["email"],
        image: json["image"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        fullMobile: json["full_mobile"],
        emailVerified: json["email_verified"],
        kycVerified: json["kyc_verified"],
        twoFactorVerified: json["two_factor_verified"],
        twoFactorStatus: json["two_factor_status"],
        twoFactorSecret: json["two_factor_secret"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "firstname": firstname,
        "lastname": lastname,
        "fullname": fullname,
        "username": username,
        "email": email,
        "image": image,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "full_mobile": fullMobile,
        "email_verified": emailVerified,
        "kyc_verified": kycVerified,
        "two_factor_verified": twoFactorVerified,
        "two_factor_status": twoFactorStatus,
        "two_factor_secret": twoFactorSecret,
      };
}

class Wallet {
  int id;
  int userId;
  int currencyId;
  double balance;
  int status;
  DateTime createdAt;
  Currency currency;

  Wallet({
    required this.id,
    required this.userId,
    required this.currencyId,
    required this.balance,
    required this.status,
    required this.createdAt,
    required this.currency,
  });

  factory Wallet.fromJson(Map<String, dynamic> json) => Wallet(
        id: json["id"],
        userId: json["user_id"],
        currencyId: json["currency_id"],
        balance: json["balance"]?.toDouble(),
        status: json["status"],
        createdAt: DateTime.parse(json["created_at"]),
        currency: Currency.fromJson(json["currency"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "user_id": userId,
        "currency_id": currencyId,
        "balance": balance,
        "status": status,
        "created_at": createdAt.toIso8601String(),
        "currency": currency.toJson(),
      };
}

class Currency {
  int id;
  String code;
  bool both;
  bool senderCurrency;
  bool receiverCurrency;
  String editData;

  Currency({
    required this.id,
    required this.code,
    required this.both,
    required this.senderCurrency,
    required this.receiverCurrency,
    required this.editData,
  });

  factory Currency.fromJson(Map<String, dynamic> json) => Currency(
        id: json["id"],
        code: json["code"],
        both: json["both"],
        senderCurrency: json["senderCurrency"],
        receiverCurrency: json["receiverCurrency"],
        editData: json["editData"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "code": code,
        "both": both,
        "senderCurrency": senderCurrency,
        "receiverCurrency": receiverCurrency,
        "editData": editData,
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

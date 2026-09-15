import 'dart:convert';

SendMoneyModel sendMoneyModelFromJson(String str) =>
    SendMoneyModel.fromJson(json.decode(str));

String sendMoneyModelToJson(SendMoneyModel data) => json.encode(data.toJson());

class SendMoneyModel {
  Message message;
  Data data;
  String type;

  SendMoneyModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory SendMoneyModel.fromJson(Map<String, dynamic> json) => SendMoneyModel(
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
  List<UserWallet> userWallets;
  List<Currency> receiverCurrencies;
  Charges charges;

  Data({
    required this.userWallets,
    required this.receiverCurrencies,
    required this.charges,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        userWallets: List<UserWallet>.from(
            json["user_wallets"].map((x) => UserWallet.fromJson(x))),
        receiverCurrencies: List<Currency>.from(
            json["receiver_currencies"].map((x) => Currency.fromJson(x))),
        charges: Charges.fromJson(json["charges"]),
      );

  Map<String, dynamic> toJson() => {
        "user_wallets": List<dynamic>.from(userWallets.map((x) => x.toJson())),
        "receiver_currencies":
            List<dynamic>.from(receiverCurrencies.map((x) => x.toJson())),
        "charges": charges.toJson(),
      };
}

class Charges {
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;
  dynamic monthlyLimit;
  dynamic dailyLimit;
  int status;

  Charges({
    required this.title,
    required this.fixedCharge,
    required this.percentCharge,
    required this.minLimit,
    required this.maxLimit,
    required this.monthlyLimit,
    required this.dailyLimit,
    required this.status,
  });

  factory Charges.fromJson(Map<String, dynamic> json) => Charges(
        title: json["title"],
        fixedCharge: json["fixed_charge"].toDouble(),
        percentCharge: json["percent_charge"].toDouble(),
        minLimit: json["min_limit"].toDouble(),
        maxLimit: json["max_limit"].toDouble(),
        monthlyLimit: json["monthly_limit"].toDouble(),
        dailyLimit: json["daily_limit"].toDouble(),
        status: json["status"],
      );

  Map<String, dynamic> toJson() => {
        "title": title,
        "fixed_charge": fixedCharge,
        "percent_charge": percentCharge,
        "min_limit": minLimit,
        "max_limit": maxLimit,
        "monthly_limit": monthlyLimit,
        "daily_limit": dailyLimit,
        "status": status,
      };
}

class Currency {
  String code;
  int rate;
  int? status;
  int? currencyDefault;
  bool both;
  bool senderCurrency;
  bool receiverCurrency;
  String editData;
  int? id;

  Currency({
    required this.code,
    required this.rate,
    this.status,
    this.currencyDefault,
    required this.both,
    required this.senderCurrency,
    required this.receiverCurrency,
    required this.editData,
    this.id,
  });

  factory Currency.fromJson(Map<String, dynamic> json) => Currency(
        code: json["code"],
        rate: json["rate"],
        status: json["status"],
        currencyDefault: json["default"],
        both: json["both"],
        senderCurrency: json["senderCurrency"],
        receiverCurrency: json["receiverCurrency"],
        editData: json["editData"],
        id: json["id"],
      );

  Map<String, dynamic> toJson() => {
        "code": code,
        "rate": rate,
        "status": status,
        "default": currencyDefault,
        "both": both,
        "senderCurrency": senderCurrency,
        "receiverCurrency": receiverCurrency,
        "editData": editData,
        "id": id,
      };
}

class UserWallet {
  dynamic balance;
  dynamic profitBalance;
  int status;
  Currency currency;

  UserWallet({
    required this.balance,
    required this.profitBalance,
    required this.status,
    required this.currency,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"],
        profitBalance: json["profit_balance"],
        status: json["status"],
        currency: Currency.fromJson(json["currency"]),
      );

  Map<String, dynamic> toJson() => {
        "balance": balance,
        "profit_balance": profitBalance,
        "status": status,
        "currency": currency.toJson(),
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

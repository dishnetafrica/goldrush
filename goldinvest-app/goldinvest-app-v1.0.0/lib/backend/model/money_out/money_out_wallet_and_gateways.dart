import 'dart:convert';

import '../../../widgets/drop_down/custom_dropdown_menu.dart';

MoneyOutWalletAndGatewaysModel moneyOutWalletAndGatewaysModelFromJson(
        String str) =>
    MoneyOutWalletAndGatewaysModel.fromJson(json.decode(str));

String moneyOutWalletAndGatewaysModelToJson(
        MoneyOutWalletAndGatewaysModel data) =>
    json.encode(data.toJson());

class MoneyOutWalletAndGatewaysModel {
  Message message;
  Data data;
  String type;

  MoneyOutWalletAndGatewaysModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory MoneyOutWalletAndGatewaysModel.fromJson(Map<String, dynamic> json) =>
      MoneyOutWalletAndGatewaysModel(
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
  BalanceType balanceType;
  List<UserWallet> userWallets;
  List<PaymentGateway> paymentGateways;

  Data({
    required this.balanceType,
    required this.userWallets,
    required this.paymentGateways,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        balanceType: BalanceType.fromJson(json["balance_type"]),
        userWallets: List<UserWallet>.from(
            json["user_wallets"].map((x) => UserWallet.fromJson(x))),
        paymentGateways: List<PaymentGateway>.from(
            json["payment_gateways"].map((x) => PaymentGateway.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "balance_type": balanceType.toJson(),
        "user_wallets": List<dynamic>.from(userWallets.map((x) => x.toJson())),
        "payment_gateways":
            List<dynamic>.from(paymentGateways.map((x) => x.toJson())),
      };
}

class BalanceType {
  bool status;
  String message;
  List<Type> types;

  BalanceType({
    required this.status,
    required this.message,
    required this.types,
  });

  factory BalanceType.fromJson(Map<String, dynamic> json) => BalanceType(
        status: json["status"],
        message: json["message"],
        types: List<Type>.from(json["types"].map((x) => Type.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "status": status,
        "message": message,
        "types": List<dynamic>.from(types.map((x) => x.toJson())),
      };
}

class Type implements DropdownMenuModel {
  String name;
  String value;

  Type({
    required this.name,
    required this.value,
  });

  factory Type.fromJson(Map<String, dynamic> json) => Type(
        name: json["name"],
        value: json["value"],
      );

  Map<String, dynamic> toJson() => {
        "name": name,
        "value": value,
      };

  @override
  String get title => name;
}

class PaymentGateway implements DropdownMenuModel {
  int id;
  String type;
  String name;
  String alias;
  String desc;
  int status;
  List<CurrencyElement> currencies;

  PaymentGateway({
    required this.id,
    required this.type,
    required this.name,
    required this.alias,
    required this.desc,
    required this.status,
    required this.currencies,
  });

  factory PaymentGateway.fromJson(Map<String, dynamic> json) => PaymentGateway(
        id: json["id"],
        type: json["type"],
        name: json["name"],
        alias: json["alias"],
        desc: json["desc"],
        status: json["status"],
        currencies: List<CurrencyElement>.from(
            json["currencies"].map((x) => CurrencyElement.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "name": name,
        "alias": alias,
        "desc": desc,
        "status": status,
        "currencies": List<dynamic>.from(currencies.map((x) => x.toJson())),
      };

  @override
  String get title => name;
}

class CurrencyElement {
  String gatewayAlias;
  int id;
  String alias;
  int paymentGatewayId;
  String name;
  String currencyCode;
  String currencySymbol;
  dynamic image;
  int minLimit;
  int maxLimit;
  int percentCharge;
  int fixedCharge;
  double rate;
  DateTime createdAt;
  DateTime updatedAt;

  CurrencyElement({
    required this.gatewayAlias,
    required this.id,
    required this.alias,
    required this.paymentGatewayId,
    required this.name,
    required this.currencyCode,
    required this.currencySymbol,
    required this.image,
    required this.minLimit,
    required this.maxLimit,
    required this.percentCharge,
    required this.fixedCharge,
    required this.rate,
    required this.createdAt,
    required this.updatedAt,
  });

  factory CurrencyElement.fromJson(Map<String, dynamic> json) =>
      CurrencyElement(
        gatewayAlias: json["gateway_alias"],
        id: json["id"],
        alias: json["alias"],
        paymentGatewayId: json["payment_gateway_id"],
        name: json["name"],
        currencyCode: json["currency_code"],
        currencySymbol: json["currency_symbol"],
        image: json["image"],
        minLimit: json["min_limit"],
        maxLimit: json["max_limit"],
        percentCharge: json["percent_charge"],
        fixedCharge: json["fixed_charge"],
        rate: json["rate"]?.toDouble(),
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "gateway_alias": gatewayAlias,
        "id": id,
        "alias": alias,
        "payment_gateway_id": paymentGatewayId,
        "name": name,
        "currency_code": currencyCode,
        "currency_symbol": currencySymbol,
        "image": image,
        "min_limit": minLimit,
        "max_limit": maxLimit,
        "percent_charge": percentCharge,
        "fixed_charge": fixedCharge,
        "rate": rate,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
      };
}

class UserWallet {
  dynamic balance;
  dynamic profitBalance;
  int status;
  UserWalletCurrency currency;

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
        currency: UserWalletCurrency.fromJson(json["currency"]),
      );

  Map<String, dynamic> toJson() => {
        "balance": balance,
        "profit_balance": profitBalance,
        "status": status,
        "currency": currency.toJson(),
      };
}

class UserWalletCurrency {
  int id;
  String code;
  int rate;
  bool both;
  bool senderCurrency;
  bool receiverCurrency;
  String editData;

  UserWalletCurrency({
    required this.id,
    required this.code,
    required this.rate,
    required this.both,
    required this.senderCurrency,
    required this.receiverCurrency,
    required this.editData,
  });

  factory UserWalletCurrency.fromJson(Map<String, dynamic> json) =>
      UserWalletCurrency(
        id: json["id"],
        code: json["code"],
        rate: json["rate"],
        both: json["both"],
        senderCurrency: json["senderCurrency"],
        receiverCurrency: json["receiverCurrency"],
        editData: json["editData"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "code": code,
        "rate": rate,
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

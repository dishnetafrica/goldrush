import 'dart:convert';

import '../../../widgets/drop_down/custom_dropdown_menu.dart';

AddMoneyPaymentGatewayModel addMoneyPaymentGatewayModelFromJson(String str) =>
    AddMoneyPaymentGatewayModel.fromJson(json.decode(str));

String addMoneyPaymentGatewayModelToJson(AddMoneyPaymentGatewayModel data) =>
    json.encode(data.toJson());

class AddMoneyPaymentGatewayModel {
  Message message;
  Data data;
  String type;

  AddMoneyPaymentGatewayModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory AddMoneyPaymentGatewayModel.fromJson(Map<String, dynamic> json) =>
      AddMoneyPaymentGatewayModel(
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
  List<PaymentGateway> paymentGateways;
  dynamic availableBalance;

  Data({
    required this.paymentGateways,
    required this.availableBalance,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        paymentGateways: List<PaymentGateway>.from(
            json["payment_gateways"].map((x) => PaymentGateway.fromJson(x))),
        availableBalance: json["available_balance"],
      );

  Map<String, dynamic> toJson() => {
        "payment_gateways":
            List<dynamic>.from(paymentGateways.map((x) => x.toJson())),
        "available_balance": availableBalance,
      };
}

class PaymentGateway {
  int id;
  String type;
  String name;
  int crypto;
  String? desc;
  int status;
  List<Currency> currencies;

  PaymentGateway({
    required this.id,
    required this.type,
    required this.name,
    required this.crypto,
    required this.desc,
    required this.status,
    required this.currencies,
  });

  factory PaymentGateway.fromJson(Map<String, dynamic> json) => PaymentGateway(
        id: json["id"],
        type: json["type"],
        name: json["name"],
        crypto: json["crypto"],
        desc: json["desc"],
        status: json["status"],
        currencies: List<Currency>.from(
            json["currencies"].map((x) => Currency.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "name": name,
        "crypto": crypto,
        "desc": desc,
        "status": status,
        "currencies": List<dynamic>.from(currencies.map((x) => x.toJson())),
      };
}

class Currency implements DropdownMenuModel {
  int id;
  int paymentGatewayId;
  String name;
  String alias;
  String? type;
  String currencyCode;
  String? currencySymbol;
  dynamic image;
  double minLimit;
  double maxLimit;
  double percentCharge;
  double fixedCharge;
  double rate;
  DateTime createdAt;
  DateTime updatedAt;
  String? selectPaymentGatewayName;

  Currency({
    required this.id,
    required this.paymentGatewayId,
    required this.name,
    this.selectPaymentGatewayName,
    this.type,
    required this.alias,
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

  factory Currency.fromJson(Map<String, dynamic> json) => Currency(
        id: json["id"],
        paymentGatewayId: json["payment_gateway_id"],
        name: json["name"],
        alias: json["alias"],
        selectPaymentGatewayName: json["selectPaymentGatewayName"] ?? '',
        type: json["type"] ?? '',
        currencyCode: json["currency_code"],
        currencySymbol: json["currency_symbol"],
        image: json["image"],
        minLimit: json["min_limit"].toDouble(),
        maxLimit: json["max_limit"].toDouble(),
        percentCharge: json["percent_charge"].toDouble(),
        fixedCharge: json["fixed_charge"].toDouble(),
        rate: json["rate"]?.toDouble(),
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "payment_gateway_id": paymentGatewayId,
        "name": name,
        "selectPaymentGatewayName": selectPaymentGatewayName,
        "type": type,
        "alias": alias,
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

  @override
  String get title => "$name  ${type != "AUTOMATIC" ? type : ""}";
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

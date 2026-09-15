import 'dart:convert';

TatumGatewayModel tatumGatewayModelFromJson(String str) =>
    TatumGatewayModel.fromJson(json.decode(str));

String tatumGatewayModelToJson(TatumGatewayModel data) =>
    json.encode(data.toJson());

class TatumGatewayModel {
  Message message;
  Data data;
  String type;

  TatumGatewayModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory TatumGatewayModel.fromJson(Map<String, dynamic> json) =>
      TatumGatewayModel(
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
  bool redirectUrl;
  List<dynamic> redirectLinks;
  String actionType;
  AddressInfo addressInfo;
  // PaymentInformations paymentInformations;

  Data({
    required this.redirectUrl,
    required this.redirectLinks,
    // required this.paymentInformations,
    required this.actionType,
    required this.addressInfo,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        // paymentInformations:
        // PaymentInformations.fromJson(json["payment_informations"]),
        redirectUrl: json["redirect_url"],
        redirectLinks: List<dynamic>.from(json["redirect_links"].map((x) => x)),
        actionType: json["action_type"],
        addressInfo: AddressInfo.fromJson(json["address_info"]),
      );

  Map<String, dynamic> toJson() => {
        // "payment_informations": paymentInformations.toJson(),
        "redirect_url": redirectUrl,
        "redirect_links": List<dynamic>.from(redirectLinks.map((x) => x)),
        "action_type": actionType,
        "address_info": addressInfo.toJson(),
      };
}

class AddressInfo {
  String coin;
  String address;
  List<InputField> inputFields;
  String submitUrl;

  AddressInfo({
    required this.coin,
    required this.address,
    required this.inputFields,
    required this.submitUrl,
  });

  factory AddressInfo.fromJson(Map<String, dynamic> json) => AddressInfo(
        coin: json["coin"],
        address: json["address"],
        inputFields: List<InputField>.from(
            json["input_fields"].map((x) => InputField.fromJson(x))),
        submitUrl: json["submit_url"],
      );

  Map<String, dynamic> toJson() => {
        "coin": coin,
        "address": address,
        "input_fields": List<dynamic>.from(inputFields.map((x) => x.toJson())),
        "submit_url": submitUrl,
      };
}

class InputField {
  String type;
  String label;
  String placeholder;
  String name;
  bool required;
  Validation validation;

  InputField({
    required this.type,
    required this.label,
    required this.placeholder,
    required this.name,
    required this.required,
    required this.validation,
  });

  factory InputField.fromJson(Map<String, dynamic> json) => InputField(
        type: json["type"],
        label: json["label"],
        placeholder: json["placeholder"],
        name: json["name"],
        required: json["required"],
        validation: Validation.fromJson(json["validation"]),
      );

  Map<String, dynamic> toJson() => {
        "type": type,
        "label": label,
        "placeholder": placeholder,
        "name": name,
        "required": required,
        "validation": validation.toJson(),
      };
}

class Validation {
  String min;
  String max;
  bool required;

  Validation({
    required this.min,
    required this.max,
    required this.required,
  });

  factory Validation.fromJson(Map<String, dynamic> json) => Validation(
        min: json["min"],
        max: json["max"],
        required: json["required"],
      );

  Map<String, dynamic> toJson() => {
        "min": min,
        "max": max,
        "required": required,
      };
}

// class PaymentInformations {
//   final String trx;
//   final String gatewayCurrencyName;
//   final String requestAmount;
//   final String exchangeRate;
//   final String totalCharge;
//   final String willGet;
//   final String payableAmount;

//   PaymentInformations({
//     required this.trx,
//     required this.gatewayCurrencyName,
//     required this.requestAmount,
//     required this.exchangeRate,
//     required this.totalCharge,
//     required this.willGet,
//     required this.payableAmount,
//   });

//   factory PaymentInformations.fromJson(Map<String, dynamic> json) =>
//       PaymentInformations(
//         trx: json["trx"],
//         gatewayCurrencyName: json["gateway_currency_name"],
//         requestAmount: json["request_amount"],
//         exchangeRate: json["exchange_rate"],
//         totalCharge: json["total_charge"],
//         willGet: json["will_get"],
//         payableAmount: json["payable_amount"],
//       );

//   Map<String, dynamic> toJson() => {
//         "trx": trx,
//         "gateway_currency_name": gatewayCurrencyName,
//         "request_amount": requestAmount,
//         "exchange_rate": exchangeRate,
//         "total_charge": totalCharge,
//         "will_get": willGet,
//         "payable_amount": payableAmount,
//       };
// }

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

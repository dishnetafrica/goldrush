import 'dart:convert';

CheckoutSuccessModel checkoutSuccessModelFromJson(String str) =>
    CheckoutSuccessModel.fromJson(json.decode(str));

String checkoutSuccessModelToJson(CheckoutSuccessModel data) =>
    json.encode(data.toJson());

class CheckoutSuccessModel {
  Message message;
  Data data;
  String type;

  CheckoutSuccessModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory CheckoutSuccessModel.fromJson(Map<String, dynamic> json) =>
      CheckoutSuccessModel(
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
  String item;
  dynamic quantity;
  double totalAmount;
  String paymentType;
  int orderStatus;
  String mobile;
  String address;
  String city;
  String zipCode;
  String state;
  String country;
  double price;
  double charge;

  Data({
    required this.item,
    required this.quantity,
    required this.totalAmount,
    required this.paymentType,
    required this.orderStatus,
    required this.mobile,
    required this.address,
    required this.city,
    required this.zipCode,
    required this.state,
    required this.country,
    required this.price,
    required this.charge,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        item: json["item"],
        quantity: json["quantity"].toString(),
        totalAmount: json["total_amount"].toDouble(),
        paymentType: json["payment_type"],
        orderStatus: json["order_status"],
        mobile: json["mobile"],
        address: json["address"],
        city: json["city"],
        zipCode: json["zip_code"],
        state: json["state"],
        country: json["country"],
        price: json["price"].toDouble(),
        charge: json["charge"].toDouble(),
      );

  Map<String, dynamic> toJson() => {
        "item": item,
        "quantity": quantity,
        "total_amount": totalAmount,
        "payment_type": paymentType,
        "order_status": orderStatus,
        "mobile": mobile,
        "address": address,
        "city": city,
        "zip_code": zipCode,
        "state": state,
        "country": country,
        "price": price,
        "charge": charge,
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

import 'dart:convert';

OrderModel orderModelFromJson(String str) =>
    OrderModel.fromJson(json.decode(str));

String orderModelToJson(OrderModel data) => json.encode(data.toJson());

class OrderModel {
  Message message;
  Data data;
  String type;

  OrderModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) => OrderModel(
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
  List<Order> order;

  Data({
    required this.instructions,
    required this.order,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        instructions: Instructions.fromJson(json["instructions"]),
        order: List<Order>.from(json["order"].map((x) => Order.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "instructions": instructions.toJson(),
        "order": List<dynamic>.from(order.map((x) => x.toJson())),
      };
}

class Instructions {
  String orderStatus;

  Instructions({
    required this.orderStatus,
  });

  factory Instructions.fromJson(Map<String, dynamic> json) => Instructions(
        orderStatus: json["order_status"],
      );

  Map<String, dynamic> toJson() => {
        "order_status": orderStatus,
      };
}

class Order {
  int id;
  String item;
  int quantity;
  dynamic totalAmount;
  String paymentType;
  DateTime date;
  int orderStatus;

  Order({
    required this.id,
    required this.item,
    required this.quantity,
    required this.totalAmount,
    required this.paymentType,
    required this.date,
    required this.orderStatus,
  });

  factory Order.fromJson(Map<String, dynamic> json) => Order(
        id: json["id"],
        item: json["item"],
        quantity: json["quantity"],
        totalAmount: json["total_amount"],
        paymentType: json["payment_type"],
        date: DateTime.parse(json["date"]),
        orderStatus: json["order_status"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "item": item,
        "quantity": quantity,
        "total_amount": totalAmount,
        "payment_type": paymentType,
        "date": date.toIso8601String(),
        "order_status": orderStatus,
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

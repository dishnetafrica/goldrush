import 'dart:convert';

CheckOutModel checkOutModelFromJson(String str) =>
    CheckOutModel.fromJson(json.decode(str));

String checkOutModelToJson(CheckOutModel data) => json.encode(data.toJson());

class CheckOutModel {
  Message message;
  Data data;
  String type;

  CheckOutModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory CheckOutModel.fromJson(Map<String, dynamic> json) => CheckOutModel(
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
  String baseUrl;
  String imagePath;
  PaymentType paymentType;
  String availableBalance;
  Gold gold;

  Data({
    required this.baseUrl,
    required this.imagePath,
    required this.paymentType,
    required this.availableBalance,
    required this.gold,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseUrl: json["base_url"],
        imagePath: json["image_path"],
        paymentType: PaymentType.fromJson(json["payment_type"]),
        availableBalance: json["available_balance"],
        gold: Gold.fromJson(json["gold"]),
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "image_path": imagePath,
        "payment_type": paymentType.toJson(),
        "available_balance": availableBalance,
        "gold": gold.toJson(),
      };
}

class Gold {
  int id;
  String title;
  String slug;
  String type;
  int price;
  dynamic charge;
  String weight;
  String purity;
  String manufacturer;
  String countryOfOrigin;
  String image;

  Gold({
    required this.id,
    required this.title,
    required this.slug,
    required this.type,
    required this.price,
    required this.charge,
    required this.weight,
    required this.purity,
    required this.manufacturer,
    required this.countryOfOrigin,
    required this.image,
  });

  factory Gold.fromJson(Map<String, dynamic> json) => Gold(
        id: json["id"],
        title: json["title"],
        slug: json["slug"],
        type: json["type"],
        price: json["price"],
        charge: json["charge"],
        weight: json["weight"],
        purity: json["purity"],
        manufacturer: json["manufacturer"],
        countryOfOrigin: json["country_of_origin"],
        image: json["image"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "title": title,
        "slug": slug,
        "type": type,
        "price": price,
        "charge": charge,
        "weight": weight,
        "purity": purity,
        "manufacturer": manufacturer,
        "country_of_origin": countryOfOrigin,
        "image": image,
      };
}

class PaymentType {
  String wallet;
  String cashOnDelivery;

  PaymentType({
    required this.wallet,
    required this.cashOnDelivery,
  });

  factory PaymentType.fromJson(Map<String, dynamic> json) => PaymentType(
        wallet: json["wallet"],
        cashOnDelivery: json["cash_on_delivery"],
      );

  Map<String, dynamic> toJson() => {
        "wallet": wallet,
        "cash_on_delivery": cashOnDelivery,
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

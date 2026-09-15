import 'dart:convert';

GoldStoreModel goldStoreModelFromJson(String str) =>
    GoldStoreModel.fromJson(json.decode(str));

String goldStoreModelToJson(GoldStoreModel data) => json.encode(data.toJson());

class GoldStoreModel {
  Message message;
  Data data;
  String type;

  GoldStoreModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory GoldStoreModel.fromJson(Map<String, dynamic> json) => GoldStoreModel(
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
  List<Gold> golds;

  Data({
    required this.baseUrl,
    required this.imagePath,
    required this.golds,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseUrl: json["base_url"],
        imagePath: json["image_path"],
        golds: List<Gold>.from(json["golds"].map((x) => Gold.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "image_path": imagePath,
        "golds": List<dynamic>.from(golds.map((x) => x.toJson())),
      };
}

class Gold {
  int id;
  String title;
  String slug;
  String type;
  double price;
  double charge;
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
        price: json["price"].toDouble(),
        charge: json["charge"].toDouble(),
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

import 'dart:convert';

// ignore: non_constant_identifier_names
InvestPlanModel InvestPlanModelFromJson(String str) =>
    InvestPlanModel.fromJson(json.decode(str));

// ignore: non_constant_identifier_names
String InvestPlanModelToJson(InvestPlanModel data) =>
    json.encode(data.toJson());

class InvestPlanModel {
  Message message;
  Data data;
  String type;

  InvestPlanModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory InvestPlanModel.fromJson(Map<String, dynamic> json) =>
      InvestPlanModel(
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
  List<Plan> plans;

  Data({
    required this.baseUrl,
    required this.imagePath,
    required this.plans,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseUrl: json["base_url"],
        imagePath: json["image_path"],
        plans: List<Plan>.from(json["plans"].map((x) => Plan.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "image_path": imagePath,
        "plans": List<dynamic>.from(plans.map((x) => x.toJson())),
      };
}

class Plan {
  int id;
  String name;
  String title;
  String slug;
  int planDuration;
  String profitReturnType;
  dynamic minimumInvestment;
  dynamic minimumInvestmentOffer;
  dynamic maximumInvestment;
  dynamic profit;
  dynamic profitPercentage;
  String image;

  Plan({
    required this.id,
    required this.name,
    required this.title,
    required this.slug,
    required this.planDuration,
    required this.profitReturnType,
    this.minimumInvestment,
    this.minimumInvestmentOffer,
    required this.maximumInvestment,
    required this.profit,
    required this.profitPercentage,
    required this.image,
  });

  factory Plan.fromJson(Map<String, dynamic> json) => Plan(
        id: json["id"],
        name: json["name"],
        title: json["title"],
        slug: json["slug"],
        planDuration: json["plan_duration"],
        profitReturnType: json["profit_return_type"],
        minimumInvestment: json["minimum_investment"],
        minimumInvestmentOffer: json["minimum_investment_offer"],
        maximumInvestment: json["maximum_investment"],
        profit: json["profit"].toDouble(),
        profitPercentage: json["profit_percentage"].toDouble(),
        image: json["image"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "title": title,
        "slug": slug,
        "plan_duration": planDuration,
        "profit_return_type": profitReturnType,
        "minimum_investment": minimumInvestment,
        "minimum_investment_offer": minimumInvestmentOffer,
        "maximum_investment": maximumInvestment,
        "profit": profit,
        "profit_percentage": profitPercentage,
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

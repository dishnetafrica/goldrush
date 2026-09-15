import 'dart:convert';

ProfitModel profitModelFromJson(String str) =>
    ProfitModel.fromJson(json.decode(str));

String profitModelToJson(ProfitModel data) => json.encode(data.toJson());

class ProfitModel {
  Message message;
  Data data;
  String type;

  ProfitModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory ProfitModel.fromJson(Map<String, dynamic> json) => ProfitModel(
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
  List<Profit> profits;

  Data({
    required this.profits,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        profits:
            List<Profit>.from(json["profits"].map((x) => Profit.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "profits": List<dynamic>.from(profits.map((x) => x.toJson())),
      };
}

class Profit {
  String title;
  int duration;
  double profitAmount;
  int investmentAmount;
  DateTime createdAt;

  Profit({
    required this.title,
    required this.duration,
    required this.profitAmount,
    required this.investmentAmount,
    required this.createdAt,
  });

  factory Profit.fromJson(Map<String, dynamic> json) => Profit(
        title: json["title"],
        duration: json["duration"],
        profitAmount: json["profit_amount"]?.toDouble(),
        investmentAmount: json["investment_amount"],
        createdAt: DateTime.parse(json["created_at"]),
      );

  Map<String, dynamic> toJson() => {
        "title": title,
        "duration": duration,
        "profit_amount": profitAmount,
        "investment_amount": investmentAmount,
        "created_at": createdAt.toIso8601String(),
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

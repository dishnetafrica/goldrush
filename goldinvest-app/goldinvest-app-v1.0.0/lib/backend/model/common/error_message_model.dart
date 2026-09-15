import 'dart:convert';

ErrorResponse errorResponseFromJson(String str) =>
    ErrorResponse.fromJson(json.decode(str));

String errorResponseToJson(ErrorResponse data) => json.encode(data.toJson());

class ErrorResponse {
  Message message;
  List<dynamic> data;
  String type;

  ErrorResponse({
    required this.message,
    required this.data,
    required this.type,
  });

  factory ErrorResponse.fromJson(Map<String, dynamic> json) => ErrorResponse(
        message: Message.fromJson(json["message"]),
        data: List<dynamic>.from(json["data"].map((x) => x)),
        type: json["type"],
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": List<dynamic>.from(data.map((x) => x)),
        "type": type,
      };
}

class Message {
  String error;

  Message({
    required this.error,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        error: json["error"].toString(),
      );

  Map<String, dynamic> toJson() => {
        "error": error,
      };
}

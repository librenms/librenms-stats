function fetch_chart_data(chart, div) {
    $.ajax({
        type: "GET",
        dataType: 'json',
        url: "./data.php",
        data: {id: div}
    })
        .done(function (data) {
            $( "#"+div+"-spinner" ).remove();
            chart.setData(data);
        })
        .fail(function (jqXHR, textStatus) {
            $( "#"+div+"-spinner" ).remove();
            // If there is no communication between the server, show an error
            console.log(textStatus);
        });
}

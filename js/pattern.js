function search() {
    $.ajax({
        type: "POST",
        url: "/source/webservices.php",
        data: {
            searchinput: $("#searchinput").val()
        },
        async: false,
        success: function (response) {

            html = "";

            if(response.length == 0){

                html += `<tr>`;
                html += `<td colspan='2'>Nenhum Resultado Encontrado</td>`;
                html += `</tr>`;

            } else {
                
                $(response).each(function(indice, data) {
                    html += `<tr>`;
                    html += `<td class='porc'>${data.NM_PLAYER}</td>`;
                    html += `<td>${data.NR_PLAYER_KILL}</td>`;
                    html += `</tr>`;
                });

            }

            $("#response").html(html);

        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            console.log("Erro, Desculpe!");
        }
    });
}
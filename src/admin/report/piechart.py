import matplotlib.pyplot as plt
import sys

filename = sys.argv[1]
title = sys.argv[2]

label = []
value = []
color = []
with open(filename, "r") as f:
    for line in f:
        tokens = line.split()
        c = tokens[-1]
        problem_data = " ".join(tokens[0:-1])
        l, v = problem_data.split(":")
        if int(v) > 0:
            label.append(l + "(" + v + ")")
            value.append(v)
            color.append(c)


wedges, texts, autotexts = plt.pie(
    value,
    colors=color,
    autopct=lambda p: "{:.1f}%".format(round(p)) if p > 0 else "",
    pctdistance=1.2,
    wedgeprops={"edgecolor": "black", "linewidth": 1, "antialiased": False},
)

plt.legend(wedges, label, loc="center left", bbox_to_anchor=(1.1, 0, 0.5, 1))
# Add title
plt.title(title, pad=20)
if len(sys.argv) <= 3:
    plt.legend(
        label,
        bbox_to_anchor=(1.1, 0.5),
        loc="center right",
        fontsize=10,
        bbox_transform=plt.gcf().transFigure,
        shadow=True,
    )
else:
    plt.legend(
        label,
        loc="lower center",
        bbox_to_anchor=(0.5, -0.1),
        fontsize=10,
        bbox_transform=plt.gcf().transFigure,
        shadow=True,
    )
# Show plot
# plt.tight_layout()
plt.savefig(
    filename.replace(".txt", ".png"), dpi=600, transparent=True, bbox_inches="tight"
)
